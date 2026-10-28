<x-modal-confirmation :title="__('Confirm Environment Deletion?')" :buttonTitle="__('Delete')" isErrorButton
    submitAction="delete" :actions="[__('This will delete the selected environment.')]"
    confirmationLabel="Please confirm the execution of the actions by entering the Environment Name below"
    shortConfirmationLabel="Environment Name" confirmationText="{{ $environmentName }}" :confirmWithPassword="false"
    step2ButtonText="Permanently Delete" />
