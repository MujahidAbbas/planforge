Please create a Product Requirements Document (PRD) for the following project:

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

Please generate a complete PRD in markdown format.
