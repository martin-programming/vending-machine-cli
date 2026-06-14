# Vending Machine

An interactive CLI vending machine simulation built with Laravel, following **Hexagonal Architecture** (Ports & Adapters) and **Domain-Driven Design** building blocks.

---

## Requirements

| Dependency | Version |
|---|---|
| Docker | 20+ |
| Docker Compose | v2 |
| Make | any |

No local PHP or Composer installation is required — everything runs inside the Docker container.

---

## Quick Start

```bash
# 1. Build the image
make build

# 2. Start the container (entrypoint auto-runs composer install and .env setup)
make start

# 3. Install the git pre-commit hook (for development only)
make hooks

# 4. Launch the vending machine
make vending
```

---

## Interactive Commands

Once the machine is running (`make vending`), type any of the following:

| Input | Action |
|---|---|
| `0.05` | Insert NICKEL ($0.05) |
| `0.10` | Insert DIME ($0.10) |
| `0.25` | Insert QUARTER ($0.25) |
| `1.00` | Insert DOLLAR ($1.00) |
| `GET-WATER` | Buy Water ($0.65) |
| `GET-JUICE` | Buy Juice ($1.00) |
| `GET-SODA` | Buy Soda ($1.50) |
| `RETURN` | Return all inserted coins |
| `SERVICE` | Restock the machine to its default state |
| `QUIT` / `EXIT` / `Q` | Exit the simulation |

### Example session

```
> 0.25
Inserted QUARTER ($0.25). Total: $0.25
> 0.25
Inserted QUARTER ($0.25). Total: $0.50
> 0.10
Inserted DIME ($0.10). Total: $0.60
> 0.05
Inserted NICKEL ($0.05). Total: $0.65
> GET-WATER
Dispensing Water. No change.

> 1.00
Inserted DOLLAR ($1.00). Total: $1.00
> GET-WATER
Dispensing Water. Change: $0.35 (1× QUARTER, 1× DIME).

> 0.25
Inserted QUARTER ($0.25). Total: $0.25
> RETURN
Returning $0.25 (1× QUARTER).
```

---

## Development Workflow

All commands below run inside the Docker container via `make`:

```bash
make test          # Run the full test suite
make lint          # Check code style (Laravel Pint, dry-run)
make lint-fix      # Auto-fix code style issues
make analyse       # Run static analysis (PHPStan level 10)
```

### Running a subset of tests

```bash
make test args="--filter=ChangeCalculator"
make test args="--testsuite=Unit"
make test args="--testsuite=Integration"
```

### Other container commands

```bash
make start         # Start the container in the background
make stop          # Stop and remove the container
make restart       # Restart the container
make fresh         # Rebuild from scratch (no Docker cache)
```

---

## Architecture

The project is structured around **Hexagonal Architecture** (Ports & Adapters), which keeps the domain completely isolated from the framework:

```
app/
├── Domain/                      # Pure business logic — zero Laravel imports
│   └── VendingMachine/
│       ├── Aggregates/
│       │   └── VendingMachine.php        # Aggregate root, enforces all invariants
│       ├── Entities/
│       │   └── Product.php
│       ├── ValueObjects/
│       │   ├── Money.php                 # Integer cents — no float arithmetic
│       │   ├── Coin.php                  # Backed enum: NICKEL | DIME | QUARTER | DOLLAR
│       │   ├── CoinCollection.php        # Immutable coin → quantity map
│       │   ├── ProductSelector.php       # Backed enum: WATER | JUICE | SODA (with prices)
│       │   └── DispenseResult.php        # Product + change bundle returned by selectProduct()
│       ├── Services/
│       │   └── GreedyChangeCalculator.php  # Largest denomination first
│       ├── Events/
│       │   ├── CoinInserted.php
│       │   ├── ProductDispensed.php
│       │   ├── CoinsReturned.php
│       │   └── MachineRestocked.php
│       ├── Exceptions/
│       │   ├── InsufficientFundsException.php
│       │   ├── ProductOutOfStockException.php
│       │   ├── InsufficientChangeException.php
│       │   └── InvalidCoinException.php
│       └── Contracts/                    # Ports (interfaces defined by the domain)
│           ├── ChangeCalculatorInterface.php
│           └── VendingMachineRepositoryInterface.php
│
├── Application/                 # Orchestration — no business logic, no framework code
│   └── Commands/
│       ├── InsertCoin/          InsertCoinCommand + InsertCoinHandler
│       ├── SelectProduct/       SelectProductCommand + SelectProductHandler
│       ├── ReturnCoins/         ReturnCoinsCommand + ReturnCoinsHandler
│       └── RestockMachine/      RestockMachineCommand + RestockMachineHandler
│
├── Infrastructure/              # Adapters — implements ports, uses the framework
│   ├── Console/
│   │   ├── VendingMachineCommand.php     # Artisan command (thin adapter, no business logic)
│   │   ├── InputParser.php               # Parses "0.25", "GET-WATER", "RETURN", etc.
│   │   └── OutputFormatter.php           # Formats domain results to display strings
│   └── Persistence/
│       └── InMemoryVendingMachineRepository.php   # In-memory repository (correct for a simulation)
│
└── Providers/
    └── VendingMachineServiceProvider.php  # Binds interfaces to implementations
```

### Why Hexagonal Architecture?

- **The domain has zero framework dependencies.** All business rules in `app/Domain/` can be tested without booting Laravel, and could be moved to a different framework without modification.
- **Ports (interfaces) decouple the domain from infrastructure.** `VendingMachineRepositoryInterface` is defined in the domain; `InMemoryVendingMachineRepository` is an infrastructure detail.
- **Each layer has a single responsibility.** The domain decides; the application orchestrates; the infrastructure delivers.

---

## Key Design Decisions

### Money as integers (cents)
All monetary amounts are stored as `int` cents internally (`Money(65)` = $0.65). This eliminates floating-point rounding errors entirely (`0.1 + 0.2 !== 0.3` in IEEE 754).

### Coin and ProductSelector as PHP enums
Both are backed enums, making them exhaustive, type-safe, and self-documenting. Adding a new denomination or product is a single line change with no hidden call sites.

### CoinCollection as an immutable value object
Wraps `array<int, int>` (Coin backing value → quantity). All mutating methods (`add`, `subtract`, `merge`) return new instances, preventing accidental shared-state bugs.

### VendingMachine as the aggregate root
All business invariants are enforced in one place. Exceptions are thrown — never returned — so callers cannot ignore error conditions. Inserted coins are never consumed by a failed operation.

### GreedyChangeCalculator as a Strategy
The change algorithm is injected via `ChangeCalculatorInterface`, making it swappable without modifying the domain. The default greedy implementation (largest coin first) is provably optimal for standard US denominations.

### Domain events with `pullDomainEvents()`
The aggregate records significant state changes as domain events (e.g. `ProductDispensed`, `CoinInserted`). Handlers pull them after the fact using `pullDomainEvents()` — the aggregate never dispatches directly to any bus, keeping it framework-agnostic and trivially testable.

### In-memory repository
For a CLI simulation, in-memory state is semantically correct. The repository interface is defined in the domain; swapping to a database-backed implementation requires only a new class and a single binding change in the service provider.

---

## Testing

The test suite is split into three layers that mirror the architecture:

| Suite | Location | What it tests |
|---|---|---|
| **Unit** | `tests/Unit/` | Pure domain: value objects, `GreedyChangeCalculator`, `VendingMachine` aggregate. Zero framework boot. |
| **Integration** | `tests/Integration/` | Application handlers wired with the real in-memory repository. Each use case tested end-to-end through the application layer. |
| **Feature** | `tests/Feature/` | Complete interaction scenarios: exact change, overpayment, insufficient funds, out of stock, no change available. |

```bash
make test                            # all suites
make test args="--testsuite=Unit"    # domain only
```

---

## Code Quality

| Tool | Purpose | Config |
|---|---|---|
| [Laravel Pint](https://laravel.com/docs/pint) | Code style (PSR-12 + Laravel preset) | `pint.json` |
| [Larastan](https://github.com/larastan/larastan) / PHPStan | Static analysis | `phpstan.neon` (level 10) |

PHPStan runs at **level 10** — the strictest setting. All code passes with zero errors.

A **pre-commit hook** (`.hooks/pre-commit`) automatically runs Pint on staged PHP files and re-stages any auto-fixed files. Install it once with `make hooks`.
