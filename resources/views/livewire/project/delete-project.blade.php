<x-modal-confirmation :title="__('Confirm Project Deletion?')" :buttonTitle="__('Delete Project')" isErrorButton submitAction="delete"
    :actions="[
        __('This will delete the selected project'),
        __('All Environments inside the project will be deleted as well.'),
    ]" confirmationLabel="Please confirm the execution of the actions by entering the Project Name below"
    shortConfirmationLabel="Project Name" confirmationText="{{ $projectName }}" :confirmWithPassword="false"
    step2ButtonText="Permanently Delete" />
