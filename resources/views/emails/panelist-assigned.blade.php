<x-mail::message>

Hello **{{ $panelist->first_name ?? $panelist->name }}**,

We would like to inform you that you have been assigned as a **Panelist** for the following project defense:

<x-mail::panel>
**Project Title:**  
{{ $project->title }}

<br>

**Defense Schedule:**  
{{ $scheduleText }}
</x-mail::panel>

Please log in to **Connect-Shelf** to review the complete project details, defense schedule, and your assigned panelist responsibilities.

<x-mail::button :url="url('/faculty/schedules')" color="success">
View Assigned Schedule
</x-mail::button>

Connect-Shelf,<br>
**{{ config('app.name', 'Connect-Shelf') }}**

</x-mail::message>