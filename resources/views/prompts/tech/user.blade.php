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

@if($template && count($template->sections) > 0)
## Document Structure

Please structure the Technical Specification with the following sections:

{!! $template->getFormattedSectionsForPrompt() !!}

@if($template->ai_instructions)
## Additional Instructions
{{ $template->ai_instructions }}
@endif

@endif
Based on the above PRD, please generate a complete Technical Specification in markdown format that provides a clear implementation roadmap for developers.
