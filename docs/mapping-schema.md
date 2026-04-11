# Mapping schema (draft)

The mapping describes how a tabular input (CSV/XLSX) maps into a target model.

## Fields
- `target`: target type + class/table
- `options`: delimiter/header/etc.
- `columns[]`:
  - `csv`: column name (header) or index
  - `field`: target field/property
  - `type`: string|int|float|bool|date
  - `required`: boolean
  - `transforms`: optional list
