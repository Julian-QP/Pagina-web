# Implementation Guide

This index is not the full contract. Before implementation, read the listed per-unit artifacts and filter the global artifacts by the unit name.

## administrator-login

- Behavior: `units/administrator-login/behavior.yaml` — preserve login branches and generic error behavior.
- Bindings: `units/administrator-login/bindings.yaml` — implement PHP session and POST wiring.
- Decomposition: `units/administrator-login/unit_decomposition.yaml` — candidates only; `commit: false`.
- Global filters: `wire_contracts.yaml` rows for `administrator-login`; `shared_modules.yaml` for `ddp/clases/conexion.php`; `data-model.md` for `usuarios`.
- Completion evidence: successful administrator login redirects to the panel; wrong password and non-administrator role do not create an authorized session.

## administrator-panel

- Behavior: `units/administrator-panel/behavior.yaml`.
- Bindings: `units/administrator-panel/bindings.yaml`.
- Decomposition: `units/administrator-panel/unit_decomposition.yaml`.
- Global filters: `wire_contracts.yaml` rows for `administrator-panel`; `cross_unit_state.yaml` login-to-panel flow.
- Completion evidence: direct unauthenticated access redirects to login and authenticated administrator access renders the dashboard.

## public-entry

- Behavior: `units/public-entry/behavior.yaml`.
- Bindings: `units/public-entry/bindings.yaml`.
- Decomposition: `units/public-entry/unit_decomposition.yaml`.
- Global filters: `cross_unit_state.yaml` public navigation flow.
- Completion evidence: every intended admin link reaches the canonical login route without changing public content behavior.
