{{ __('The enabled backup schedule for :name has produced no executions in the last :days day(s).', ['name' => $database_name, 'days' => $days]) }}

@if ($last_execution_at)
{{ __('The last execution was at :time.', ['time' => $last_execution_at]) }}
@else
{{ __('This schedule has never produced an execution.') }}
@endif
