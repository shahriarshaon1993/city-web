{{ $assigned ? 'A lead was assigned to you.' : 'A new lead arrived on the website.' }}

Name: {!! $lead->name !!}
Phone: {{ $lead->phone }}
@if ($lead->email)
Email: {!! $lead->email !!}
@endif
Type: {!! $typeLabel !!}
@if ($lead->project)
Project: {!! $lead->project->name !!}
@endif
@if ($lead->message)

Message:
{!! $lead->message !!}
@endif

Open the lead: {{ $leadUrl }}
