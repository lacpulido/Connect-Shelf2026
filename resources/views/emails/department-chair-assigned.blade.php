<x-mail::message>

Hello **{{ $user->first_name ?? $user->name }}**,

We would like to inform you that you have been assigned as the **Department Chairperson** of:

<x-mail::panel>
**Department:**  
{{ $department->name }}
</x-mail::panel>

<x-mail::button :url="url('/dashboard')" color="success">
Go to Connect-Shelf
</x-mail::button>

Regards,  
**{{ config('app.name', 'Connect-Shelf') }}**

</x-mail::message>