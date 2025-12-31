## Project: {{ $project->name }}

## Project Idea
{{ $project->idea }}

## Constraints
@if($project->constraints)
@foreach($project->constraints as $key => $value)
- **{{ $key }}**: {{ is_array($value) ? implode(', ', $value) : $value }}
@endforeach
@else
No specific constraints.
@endif

## Tech Spec
{{ $techSpec }}

@if($prdSummary)
## PRD Context
{{ $prdSummary }}
@endif

---

Generate implementation tasks covering ALL sections of the Tech Spec.
Order logically: migrations → models → controllers → views → tests.
Every task must reference which Tech Spec section it implements.
