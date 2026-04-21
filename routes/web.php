<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\FeaturedProjectController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SearchController;

use App\Http\Controllers\admin\AdminProjectController;
use App\Http\Controllers\admin\AssignRoleController;
use App\Http\Controllers\admin\AuthController;
use App\Http\Controllers\admin\DashboardController;
use App\Http\Controllers\admin\DepartmentController;
use App\Http\Controllers\admin\ReportController;
use App\Http\Controllers\admin\UserController;
use App\Http\Controllers\admin\SettingsController;
use App\Http\Controllers\admin\AdminNotificationController;

use App\Http\Controllers\student\ProjectController;
use App\Http\Controllers\student\SubmissionController;
use App\Http\Controllers\student\UserSearchController;
use App\Http\Controllers\student\SubmitNewPaperController;
use App\Http\Controllers\student\SubmitFinalManuscriptController;
use App\Http\Controllers\student\StudentFormsAndTemplatesController;
use App\Http\Controllers\student\StudentDashboardController;

use App\Http\Controllers\faculty\StudentsSubmissionController;
use App\Http\Controllers\faculty\ListofProjectController;
use App\Http\Controllers\faculty\ReviewDocumentController;
use App\Http\Controllers\faculty\FacultyDashboardController;
use App\Http\Controllers\faculty\DefenseSchedulesController;
use App\Http\Controllers\faculty\ScheduleActionController;

use App\Http\Controllers\DepartmentChair\AssignFocalPersonController;
use App\Http\Controllers\FocalPerson\AssignAdviserToProjectsController;
use App\Http\Controllers\FocalPerson\AssignPanelistToProjectsController;
use App\Http\Controllers\FocalPerson\SetScheduleController;
use App\Http\Controllers\FocalPerson\FormsAndTemplatesController;
use App\Http\Controllers\FocalPerson\DepartmentFacultyController;
use App\Http\Controllers\DepartmentChair\ResearchArchiveController;
use App\Http\Controllers\DepartmentChair\FinalManuscriptReviewController;
use App\Http\Controllers\ManuscriptController;
use App\Http\Controllers\public\ThesisProjectController;
use App\Http\Controllers\public\CapstoneProjectController;




//Public Routes
//Route::get('/manuscripts/search', [SearchController::class, 'search']);
Route::get('/', [FeaturedProjectController::class, 'index'])->name('home');

Route::get('/featured-projects', [FeaturedProjectController::class, 'index'])
    ->name('featured.projects');


Route::get('/reindex', function () {
    \App\Models\ProjectDocument::all()->each->searchable();
});

Route::get('/resources/thesis', [ThesisProjectController::class, 'index'])
    ->name('resources.thesis');

Route::get('/resources/thesis/{slug}', [ThesisProjectController::class, 'show'])
    ->name('resources.thesis.show');

Route::get('/resources/capstone', [CapstoneProjectController::class, 'index'])
    ->name('resources.capstone');

Route::get('/resources/capstone/{slug}', [CapstoneProjectController::class, 'show'])
    ->name('resources.capstone.show');

Route::get('/resources/about', fn() => Inertia::render('About'))
    ->name('resources.about');

Route::middleware('auth')->group(function () {
    Route::get('/manuscripts/{id}/download', [ManuscriptController::class, 'download'])
        ->name('manuscripts.download');
});


Route::get('/dashboard', function () {
    return redirect('/');
})->middleware(['auth', 'verified', 'redirect.user'])->name('dashboard');

//Admin Routes

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

    Route::middleware(['admin.auth'])->group(function () {
        Route::get('/', [DashboardController::class, 'dashboard'])->name('dashboard');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        // ================= NOTIFICATIONS =================
        Route::get('/notifications', [AdminNotificationController::class, 'index'])
            ->name('notifications.index');

        Route::post('/notifications/mark-all-read', [AdminNotificationController::class, 'markAllAsRead'])
            ->name('notifications.mark-all-read');

        Route::post('/notifications/{id}/mark-as-read', [AdminNotificationController::class, 'markAsRead'])
            ->name('notifications.mark-as-read');

        // ================= USERS =================
        Route::get('/users', [UserController::class, 'users'])->name('users');

        Route::get('/users/deactivate', [UserController::class, 'deactivatePage'])
            ->name('users.deactivate.index');

        Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])
            ->name('users.toggle-active');

        Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate'])
            ->name('users.deactivate.submit');

        Route::patch('/users/{user}/reactivate', [UserController::class, 'reactivate'])
            ->name('users.reactivate.submit');

        // ================= ASSIGN ROLE =================
        Route::get('/users/assign-role', [AssignRoleController::class, 'index'])
            ->name('users.assign-role');

        Route::post('/users/{user}/toggle-chair', [AssignRoleController::class, 'toggleChair'])
            ->name('users.toggle-chair');

        // ================= PROJECTS =================
        Route::get('/projects', [AdminProjectController::class, 'projects'])
            ->name('projects');

        Route::get('/projects/{id}', [AdminProjectController::class, 'projectShow'])
            ->name('projects.show');

        // ================= DEPARTMENTS =================
        Route::get('/departments', [DepartmentController::class, 'index'])
            ->name('departments');

        Route::post('/departments', [DepartmentController::class, 'store'])
            ->name('departments.store');

        // ================= REPORTS =================
        Route::get('/reports/user-summary', [ReportController::class, 'userSummary'])
            ->name('reports.user-summary');

        Route::get('/reports/project-summary', [ReportController::class, 'projectSummary'])
            ->name('reports.project-summary');

        // ================= SETTINGS =================
        Route::get('/settings', [SettingsController::class, 'index'])
            ->name('settings');

        Route::patch('/settings/profile', [SettingsController::class, 'updateProfile'])
            ->name('settings.update-profile');

        Route::patch('/settings/password', [SettingsController::class, 'updatePassword'])
            ->name('settings.update-password');
    });
});
Route::middleware(['auth', 'verified', 'access:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {

        // ✅ Dashboard
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])
            ->name('dashboard');

        // ✅ Submissions
        Route::get('/submissions', [SubmissionController::class, 'index'])
            ->name('submissions');

        Route::get(
            '/projects/{project:slug}/submissions/{folder}/{document}',
            [SubmissionController::class, 'show']
        )->name('submissions.show');

        Route::post(
            '/submissions/{submission}/resubmit',
            [SubmitNewPaperController::class, 'resubmit']
        )->name('submissions.resubmit');

        Route::post('/submit-paper', [SubmitNewPaperController::class, 'store'])
            ->name('submit-paper.store');

        Route::delete('/submissions/{id}', [SubmissionController::class, 'destroy'])
            ->name('submissions.destroy');

        Route::get('/submissions/file/{slug}', [SubmissionController::class, 'download'])
            ->where('slug', '.*')
            ->name('submissions.download');

        // ✅ Tasks
        Route::get('/tasks', fn() => Inertia::render('Student/Tasks'))
            ->name('tasks');

        // ✅ Final Manuscript
        Route::get('/submit-final-manuscript', [SubmitFinalManuscriptController::class, 'index'])
            ->name('submit-final-manuscript');

        Route::post('/submit-final-manuscript', [SubmitFinalManuscriptController::class, 'store'])
            ->name('submit-final-manuscript.store');

        // ✅ Projects
        Route::get('/projects/create', [ProjectController::class, 'create'])
            ->name('projects.create');

        Route::post('/projects', [ProjectController::class, 'store'])
            ->name('projects.store');

        // ✅ User Search
        Route::get('/users/search', [UserSearchController::class, 'search'])
            ->name('users.search');

        // ✅ Forms
        Route::get('/forms', [StudentFormsAndTemplatesController::class, 'index'])
            ->name('forms');

        Route::get('/forms/{form}/download', [StudentFormsAndTemplatesController::class, 'download'])
            ->name('forms.download');
    });

Route::middleware(['auth', 'verified', 'access:faculty'])
    ->prefix('faculty')
    ->name('faculty.')
    ->group(function () {

        Route::get('/dashboard', [FacultyDashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/projects', [ListofProjectController::class, 'index'])
            ->name('projects');

        Route::get('/projects/{project:slug}', [ListofProjectController::class, 'show'])
            ->name('projects.show');

        Route::get(
            '/projects/{project:slug}/submissions',
            [StudentsSubmissionController::class, 'index']
        )->name('submissions.index');

        Route::get(
            '/projects/{project:slug}/submission/{folder}/{document}',
            [StudentsSubmissionController::class, 'facultyShow']
        )->name('submission.show');

        Route::post(
            '/documents/comment/{document}',
            [ReviewDocumentController::class, 'storeComment']
        )->where('document', '.*')->name('documents.comment.store');

        Route::get(
            '/documents/view/{document}',
            [ReviewDocumentController::class, 'view']
        )->where('document', '.*')->name('documents.view');

        Route::get(
            '/documents/download/{document}',
            [ReviewDocumentController::class, 'download']
        )->where('document', '.*')->name('documents.download');

        Route::get('/schedules', [DefenseSchedulesController::class, 'index'])
            ->name('schedules');

        Route::post('/schedules/{schedule}/respond', [DefenseSchedulesController::class, 'respond'])
            ->name('schedules.respond');

        Route::post('/schedules/{schedule}/confirm', [ScheduleActionController::class, 'confirm'])
            ->name('schedules.confirm');

        Route::post('/schedules/{schedule}/request-reschedule', [ScheduleActionController::class, 'requestReschedule'])
            ->name('schedules.request-reschedule');

        Route::get('/defense-schedules', [DefenseSchedulesController::class, 'index'])
            ->name('defense-schedules.index');

        Route::post('/defense-schedules/{schedule}/respond', [DefenseSchedulesController::class, 'respond'])
            ->name('defense-schedules.respond');

        Route::post('/projects/{project}/accept-adviser', [ProjectController::class, 'acceptAdviser'])
            ->name('projects.accept-adviser');
    });
//Department Chair Routes
Route::middleware(['auth', 'verified', 'access:Department ChairPerson'])
    ->prefix('department-chair')
    ->name('departmentchair.')
    ->group(function () {

        Route::get(
            '/assign-faculty',
            [AssignFocalPersonController::class, 'index']
        )->name('assignfaculty');

        Route::post(
            '/faculty/{user}/toggle-focal',
            [AssignFocalPersonController::class, 'toggleFocal']
        )->name('toggle-focal');

        /* ADD THIS */
        Route::get(
            '/research-archives',
            [ResearchArchiveController::class, 'index']
        )->name('researcharchives');
        Route::post(
            '/manuscript/{id}/approve',
            [FinalManuscriptReviewController::class, 'approve']
        )->name('manuscript.approve');
        Route::get(
            '/manuscript/{id}/view',
            [FinalManuscriptReviewController::class, 'view']
        )->name('manuscript.view');
    });
//FocalPerson Routes
Route::middleware(['auth', 'verified', 'access:Focal Person'])
    ->prefix('focal-person')
    ->name('focalperson.')
    ->group(function () {

        Route::get(
            '/projects',
            [AssignAdviserToProjectsController::class, 'index']
        )->name('projects');

        Route::get(
            '/projects',
            [AssignAdviserToProjectsController::class, 'index']
        )->name('listofprojects');

        Route::post(
            '/projects/{project:slug}/assign',
            [AssignAdviserToProjectsController::class, 'assign']
        )->name('projects.assign');

        Route::post(
            '/schedule/store',
            [SetScheduleController::class, 'store']
        )->name('schedule.store');

        Route::get(
            '/projects/{project}/assign-panelists',
            [AssignPanelistToProjectsController::class, 'index']
        )->name('panelists.index');

        Route::post(
            '/projects/assign-panelists',
            [AssignPanelistToProjectsController::class, 'store']
        )->name('panelists.store');

        Route::get(
            '/forms-and-templates',
            [FormsAndTemplatesController::class, 'index']
        )->name('forms.templates');

        Route::post(
            '/forms/store',
            [FormsAndTemplatesController::class, 'store']
        )->name('forms.store');


        Route::get('/forms/download/{id}', [FormsAndTemplatesController::class, 'download'])
            ->name('forms.download');

        Route::get(
            '/department-faculty',
            [DepartmentFacultyController::class, 'index']
        )->name('department.faculty');

        Route::delete(
            '/forms/{id}',
            [FormsAndTemplatesController::class, 'destroy']
        )->name('forms.destroy');
    });
//Notifications
Route::middleware(['auth'])->group(function () {

    Route::get(
        '/notifications',
        [NotificationController::class, 'index']
    );

    Route::post(
        '/notifications/{id}/read',
        [NotificationController::class, 'markAsRead']
    );

    Route::post(
        '/notifications/read-all',
        [NotificationController::class, 'markAllAsRead']
    );
});

require __DIR__ . '/auth.php';
require __DIR__ . '/settings.php';
