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

@if($template && count($template->sections) > 0)
## Document Structure

Please structure the PRD with the following sections:

{!! $template->getFormattedSectionsForPrompt() !!}

@if($template->ai_instructions)
## Additional Instructions
{{ $template->ai_instructions }}
@endif
@endif

Please generate a complete PRD in markdown format.
