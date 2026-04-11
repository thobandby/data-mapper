# Architecture

Core-first, platform-agnostic design using Ports & Adapters.

- **Domain**: import concepts (mapping, job, row errors)
- **Application**: use cases (analyze, preview, run import)
- **Ports**: reader/persister/validator/transformer abstractions
- **Infrastructure**: CSV reader/sniffer, built-in transformers

Adapters live in separate packages:
- Doctrine adapter: persistence
- Symfony adapter: UI + DI
- CLI adapter: commands
