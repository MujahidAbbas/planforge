Please create a Technical Specification document based on the following PRD:

## Project Name
{{ $project->name }}

## Project Idea
{{ $project->idea }}

@if($project->constraints)
## Constraints & Preferences
@foreach($project->constraints as $key => $value)
- **{{ ucfirst($key) }}**: @if(is_array($value)){{ implode(', ', $value) }}@else{{ $value }}@endif

@endforeach
@endif

---

## Product Requirements Document (PRD)

{!! $prd !!}

---

Based on the above PRD, please generate a complete Technical Specification in markdown format that provides a clear implementation roadmap for developers.
