<x-emails.layout>
{{ __('You have been invited to ":team" on ":app".', ['team' => $team, 'app' => config('app.name')]) }}

{{ __('Please [click here](:url) to accept the invitation.', ['url' => $invitation_link]) }}

{{ __('If you have any questions, please contact the team owner.') }}<br><br>

{{ __('If it was not you who requested this invitation, please ignore this email.') }}
</x-emails.layout>
