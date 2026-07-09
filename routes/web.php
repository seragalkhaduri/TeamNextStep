<?php

use App\Livewire\AuditLogViewer;
use App\Domain\Students\Services\StudentService;
use App\Domain\Employees\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('login');
    })->name('login');

    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/');
        }

        return back()->withErrors([
            'username' => 'Invalid username or password.',
        ])->onlyInput('username');
    });
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    
    // Dashboard
    Route::get('/', function () {
        return view('welcome');
    });

    Route::get('/lang/{locale}', function (string $locale) {
        if (in_array($locale, ['en', 'ar'])) {
            session(['locale' => $locale]);
        }
        return redirect()->back();
    });

    // Logout
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    })->name('logout');

    // Redirect standalone audit logs to dashboard audit tab
    Route::get('/audit/logs', function () {
        return redirect('/')->with('active_section', 'audit-logs');
    });

    // ── Students CRUD ───────────────────────────────────────────
    Route::post('/students/create', function (Request $request, StudentService $studentService) {
        $data = $request->validate([
            'institutionalId' => ['required', 'string', 'max:50'],
            'nationalId' => ['required', 'string', 'max:50'],
            'nameEn' => ['required', 'string', 'max:255'],
            'nameAr' => ['required', 'string', 'max:255'],
            'dateOfBirth' => ['required', 'date'],
            'gender' => ['required', 'string', 'in:MALE,FEMALE'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'admissionDate' => ['required', 'date'],
            'programIds' => ['nullable', 'array'],
            'programIds.*' => ['uuid'],
            
            // Optional initial research group assignment
            'research_group_id' => ['nullable', 'uuid'],
            'research_group_role' => ['nullable', 'string', 'in:Co-I,Research-Assistant,Graduate-Researcher,External-Collaborator'],
            'research_group_workload' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $userId = Auth::id();
        
        try {
            $student = $studentService->create($data, $userId);
            
            // Assign to Research Group if specified
            if (!empty($data['research_group_id']) && !empty($data['research_group_role'])) {
                \App\Domain\ResearchGroups\Models\GroupMembership::create([
                    'id' => (string) Str::uuid(),
                    'group_id' => $data['research_group_id'],
                    'member_uimp_id' => $data['institutionalId'],
                    'member_type' => 'Student',
                    'role' => $data['research_group_role'],
                    'workload_percentage' => $data['research_group_workload'] ?? 20,
                    'start_date' => date('Y-m-d'),
                    'status' => 'Active',
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }

            return redirect()->back()
                ->with('active_section', 'students')
                ->with('success', 'Student registered successfully (and audit log generated!)');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('active_section', 'add-records')
                ->with('active_tab', 'add-student-tab')
                ->withErrors(['error' => $e->getMessage()]);
        }
    });

    Route::post('/students/{id}/edit', function (Request $request, string $id, StudentService $studentService) {
        $student = \App\Domain\Students\Models\Student::findOrFail($id);
        $data = $request->validate([
            'institutionalId' => ['required', 'string', 'max:50'],
            'nationalId' => ['required', 'string', 'max:50'],
            'nameEn' => ['required', 'string', 'max:255'],
            'nameAr' => ['required', 'string', 'max:255'],
            'dateOfBirth' => ['required', 'date'],
            'gender' => ['required', 'string', 'in:MALE,FEMALE'],
            'admissionDate' => ['required', 'date'],
        ]);

        try {
            $studentService->update($student, $data);
            return redirect()->back()
                ->with('active_section', 'students')
                ->with('success', 'Student updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('active_section', 'students')
                ->withErrors(['error' => $e->getMessage()]);
        }
    });

    Route::post('/students/{id}/delete', function (string $id, StudentService $studentService) {
        $student = \App\Domain\Students\Models\Student::findOrFail($id);
        try {
            $studentService->delete($student);
            return redirect()->back()
                ->with('active_section', 'students')
                ->with('success', 'Student deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('active_section', 'students')
                ->withErrors(['error' => $e->getMessage()]);
        }
    });

    // ── Employees CRUD ──────────────────────────────────────────
    Route::post('/employees/create', function (Request $request, EmployeeService $employeeService) {
        $data = $request->validate([
            'institutionalId' => ['required', 'string', 'max:50'],
            'staffType' => ['required', 'string', 'in:ACADEMIC,NON_ACADEMIC'],
            'nameEn' => ['required', 'string', 'max:255'],
            'nameAr' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'academicRank' => ['nullable', 'string', 'in:PROFESSOR,ASSOCIATE_PROFESSOR,ASSISTANT_PROFESSOR,LECTURER,ASSISTANT_LECTURER'],
            'hireDate' => ['required', 'date'],
            'departmentIds' => ['nullable', 'array'],
            'departmentIds.*' => ['uuid'],

            // Optional initial research group assignment
            'research_group_id' => ['nullable', 'uuid'],
            'research_group_role' => ['nullable', 'string', 'in:Co-I,Research-Assistant,Graduate-Researcher,External-Collaborator'],
            'research_group_workload' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        try {
            $employeeService->create($data);

            // Assign to Research Group if specified
            if (!empty($data['research_group_id']) && !empty($data['research_group_role'])) {
                \App\Domain\ResearchGroups\Models\GroupMembership::create([
                    'id' => (string) Str::uuid(),
                    'group_id' => $data['research_group_id'],
                    'member_uimp_id' => $data['institutionalId'],
                    'member_type' => 'Staff',
                    'role' => $data['research_group_role'],
                    'workload_percentage' => $data['research_group_workload'] ?? 20,
                    'start_date' => date('Y-m-d'),
                    'status' => 'Active',
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }

            return redirect()->back()
                ->with('active_section', 'employees')
                ->with('success', 'Employee registered successfully (and audit log generated!)');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('active_section', 'add-records')
                ->with('active_tab', 'add-employee-tab')
                ->withErrors(['error' => $e->getMessage()]);
        }
    });

    Route::post('/employees/{id}/edit', function (Request $request, string $id, EmployeeService $employeeService) {
        $employee = \App\Domain\Employees\Models\Employee::findOrFail($id);
        $data = $request->validate([
            'institutionalId' => ['required', 'string', 'max:50'],
            'staffType' => ['required', 'string', 'in:ACADEMIC,NON_ACADEMIC'],
            'nameEn' => ['required', 'string', 'max:255'],
            'nameAr' => ['required', 'string', 'max:255'],
            'academicRank' => ['nullable', 'string', 'in:PROFESSOR,ASSOCIATE_PROFESSOR,ASSISTANT_PROFESSOR,LECTURER,ASSISTANT_LECTURER'],
            'hireDate' => ['required', 'date'],
        ]);

        try {
            $employeeService->update($employee, $data);
            return redirect()->back()
                ->with('active_section', 'employees')
                ->with('success', 'Employee updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('active_section', 'employees')
                ->withErrors(['error' => $e->getMessage()]);
        }
    });

    Route::post('/employees/{id}/delete', function (string $id, EmployeeService $employeeService) {
        $employee = \App\Domain\Employees\Models\Employee::findOrFail($id);
        try {
            $employeeService->delete($employee);
            return redirect()->back()
                ->with('active_section', 'employees')
                ->with('success', 'Employee deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('active_section', 'employees')
                ->withErrors(['error' => $e->getMessage()]);
        }
    });

    // ── Research Groups CRUD ────────────────────────────────────
    Route::post('/research-groups/create', function (Request $request) {
        $data = $request->validate([
            'group_name' => ['required', 'string', 'max:255'],
            'research_field' => ['required', 'string', 'max:200'],
            'research_area' => ['required', 'string', 'max:200'],
            'status' => ['required', 'string', 'in:Draft,Active,Suspended,Archived'],
            'pi_staff_id' => ['required', 'string', 'max:100'],
            'department_ref_id' => ['nullable', 'string', 'max:100'],
            'budget_allocation' => ['nullable', 'numeric'],
            
            // Optional initial members array
            'initial_members' => ['nullable', 'array'],
        ]);

        $groupId = (string) Str::uuid();
        $creatorId = Auth::id();

        $groupData = [
            'id' => $groupId,
            'group_name' => $data['group_name'],
            'research_field' => $data['research_field'],
            'research_area' => $data['research_area'],
            'status' => $data['status'],
            'pi_staff_id' => $data['pi_staff_id'],
            'department_ref_id' => $data['department_ref_id'] ?? null,
            'budget_allocation' => $data['budget_allocation'] ?? null,
            'created_by' => $creatorId,
            'updated_by' => $creatorId,
        ];

        try {
            \App\Domain\ResearchGroups\Models\ResearchGroup::create($groupData);

            // Add PI as an implicit member
            \App\Domain\ResearchGroups\Models\GroupMembership::create([
                'id' => (string) Str::uuid(),
                'group_id' => $groupId,
                'member_uimp_id' => $data['pi_staff_id'],
                'member_type' => 'Staff',
                'role' => 'PI',
                'start_date' => date('Y-m-d'),
                'status' => 'Active',
                'created_by' => $creatorId,
                'updated_by' => $creatorId,
            ]);

            // Add other initial members
            if (!empty($data['initial_members'])) {
                foreach ($data['initial_members'] as $memberString) {
                    $parts = explode('|', $memberString);
                    if (count($parts) === 3) {
                        $mType = $parts[0];
                        $mId = $parts[1];
                        $mRole = $parts[2];
                        
                        // Prevent duplicating PI
                        if ($mId === $data['pi_staff_id']) continue;

                        \App\Domain\ResearchGroups\Models\GroupMembership::create([
                            'id' => (string) Str::uuid(),
                            'group_id' => $groupId,
                            'member_uimp_id' => $mId,
                            'member_type' => $mType,
                            'role' => $mRole,
                            'start_date' => date('Y-m-d'),
                            'status' => 'Active',
                            'created_by' => $creatorId,
                            'updated_by' => $creatorId,
                        ]);
                    }
                }
            }

            return redirect()->back()
                ->with('active_section', 'research-groups')
                ->with('success', 'Research Group registered successfully with initial members!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('active_section', 'add-records')
                ->with('active_tab', 'add-group-tab')
                ->withErrors(['error' => $e->getMessage()]);
        }
    });

    Route::post('/research-groups/{id}/edit', function (Request $request, string $id) {
        $group = \App\Domain\ResearchGroups\Models\ResearchGroup::findOrFail($id);
        $data = $request->validate([
            'group_name' => ['required', 'string', 'max:255'],
            'research_field' => ['required', 'string', 'max:200'],
            'research_area' => ['required', 'string', 'max:200'],
            'status' => ['required', 'string', 'in:Draft,Active,Suspended,Archived'],
            'pi_staff_id' => ['required', 'string', 'max:100'],
            'department_ref_id' => ['nullable', 'string', 'max:100'],
            'budget_allocation' => ['nullable', 'numeric'],
        ]);

        try {
            $group->update($data);
            return redirect()->back()
                ->with('active_section', 'research-groups')
                ->with('success', 'Research Group updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('active_section', 'research-groups')
                ->withErrors(['error' => $e->getMessage()]);
        }
    });

    Route::post('/research-groups/{id}/delete', function (string $id) {
        $group = \App\Domain\ResearchGroups\Models\ResearchGroup::findOrFail($id);
        try {
            $group->delete();
            return redirect()->back()
                ->with('active_section', 'research-groups')
                ->with('success', 'Research Group deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('active_section', 'research-groups')
                ->withErrors(['error' => $e->getMessage()]);
        }
    });

    // ── Research Projects & Contributors CRUD ───────────────────
    Route::post('/projects/create', function (Request $request) {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:500'],
            'research_group_id' => ['required', 'uuid'],
            'funding_agency' => ['required', 'string', 'max:300'],
            'budget' => ['required', 'numeric'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'status' => ['required', 'string', 'in:Planning,Active,On-Hold,Completed,Terminated'],
            
            // Optional initial contributors
            'initial_contributors' => ['nullable', 'array'],
        ]);

        $projectId = (string) Str::uuid();
        $creatorId = Auth::id();

        $projectData = [
            'id' => $projectId,
            'title' => $data['title'],
            'research_group_id' => $data['research_group_id'],
            'funding_agency' => $data['funding_agency'],
            'budget' => $data['budget'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => $data['status'],
            'created_by' => $creatorId,
            'updated_by' => $creatorId,
        ];

        try {
            \App\Domain\ResearchGroups\Models\ResearchProject::create($projectData);

            // Add initial contributors
            if (!empty($data['initial_contributors'])) {
                foreach ($data['initial_contributors'] as $contribString) {
                    $parts = explode('|', $contribString);
                    if (count($parts) === 3) {
                        $cId = $parts[1]; // member institutional id
                        $cRole = $parts[2]; // role
                        
                        \App\Domain\ResearchGroups\Models\ProjectContributor::create([
                            'id' => (string) Str::uuid(),
                            'project_id' => $projectId,
                            'member_uimp_id' => $cId,
                            'contributor_role' => $cRole,
                        ]);
                    }
                }
            }

            return redirect()->back()
                ->with('active_section', 'projects')
                ->with('success', 'Research Project registered with initial contributors successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('active_section', 'add-records')
                ->with('active_tab', 'add-project-tab')
                ->withErrors(['error' => $e->getMessage()]);
        }
    });

    Route::post('/projects/{id}/edit', function (Request $request, string $id) {
        $project = \App\Domain\ResearchGroups\Models\ResearchProject::findOrFail($id);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:500'],
            'funding_agency' => ['required', 'string', 'max:300'],
            'budget' => ['required', 'numeric'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'status' => ['required', 'string', 'in:Planning,Active,On-Hold,Completed,Terminated'],
        ]);

        try {
            $project->update($data);
            return redirect()->back()
                ->with('active_section', 'projects')
                ->with('success', 'Research Project updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('active_section', 'projects')
                ->withErrors(['error' => $e->getMessage()]);
        }
    });

    Route::post('/projects/{id}/delete', function (string $id) {
        $project = \App\Domain\ResearchGroups\Models\ResearchProject::findOrFail($id);
        try {
            $project->delete();
            return redirect()->back()
                ->with('active_section', 'projects')
                ->with('success', 'Research Project deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('active_section', 'projects')
                ->withErrors(['error' => $e->getMessage()]);
        }
    });

    // ── Project Contributors CRUD ──
    Route::post('/project-contributors/create', function (Request $request) {
        $data = $request->validate([
            'project_id' => ['required', 'uuid'],
            'member_uimp_id' => ['required', 'string', 'max:100'],
            'contributor_role' => ['required', 'string', 'max:100'],
        ]);

        try {
            \App\Domain\ResearchGroups\Models\ProjectContributor::create([
                'id' => (string) Str::uuid(),
                'project_id' => $data['project_id'],
                'member_uimp_id' => $data['member_uimp_id'],
                'contributor_role' => $data['contributor_role'],
            ]);
            return redirect()->back()
                ->with('active_section', 'projects')
                ->with('success', 'Contributor assigned successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('active_section', 'projects')
                ->withErrors(['error' => $e->getMessage()]);
        }
    });

    Route::post('/project-contributors/{id}/delete', function (string $id) {
        $contributor = \App\Domain\ResearchGroups\Models\ProjectContributor::findOrFail($id);
        try {
            $contributor->delete();
            return redirect()->back()
                ->with('active_section', 'projects')
                ->with('success', 'Contributor removed from project!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('active_section', 'projects')
                ->withErrors(['error' => $e->getMessage()]);
        }
    });

    Route::post('/project-contributors/{id}/edit', function (Request $request, string $id) {
        $contributor = \App\Domain\ResearchGroups\Models\ProjectContributor::findOrFail($id);
        $data = $request->validate([
            'contributor_role' => ['required', 'string', 'max:100'],
        ]);

        try {
            $contributor->update($data);
            return redirect()->back()
                ->with('active_section', 'projects')
                ->with('success', 'Contributor role updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('active_section', 'projects')
                ->withErrors(['error' => $e->getMessage()]);
        }
    });


    // ── Publications & Authors CRUD ─────────────────────────────
    Route::post('/publications/create', function (Request $request) {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:500'],
            'research_group_id' => ['required', 'uuid'],
            'publication_type' => ['required', 'string', 'in:Journal-Article,Conference-Paper,Book-Chapter,Technical-Report'],
            'publication_year' => ['required', 'integer'],
            'status' => ['required', 'string', 'in:In-Preparation,Submitted,Under-Review,Accepted,Published,Retracted'],
            'doi' => ['nullable', 'string', 'max:255'],
            'journal_name' => ['nullable', 'string', 'max:300'],
            'publisher' => ['nullable', 'string', 'max:300'],
            
            // Optional initial authors
            'initial_authors' => ['nullable', 'array'],
        ]);

        $pubId = (string) Str::uuid();
        $creatorId = Auth::id();

        $pubData = [
            'id' => $pubId,
            'research_group_id' => $data['research_group_id'],
            'title' => $data['title'],
            'publication_type' => $data['publication_type'],
            'publication_year' => $data['publication_year'],
            'status' => $data['status'],
            'doi' => $data['doi'] ?? null,
            'journal_name' => $data['journal_name'] ?? null,
            'publisher' => $data['publisher'] ?? null,
            'created_by' => $creatorId,
            'updated_by' => $creatorId,
        ];

        try {
            \App\Domain\ResearchGroups\Models\Publication::create($pubData);

            // Add initial authors
            if (!empty($data['initial_authors'])) {
                foreach ($data['initial_authors'] as $index => $authorString) {
                    $parts = explode('|', $authorString);
                    if (count($parts) === 3) {
                        $authId = $parts[1]; // member institutional id
                        $authContrib = $parts[2]; // contribution text
                        
                        \App\Domain\ResearchGroups\Models\PublicationAuthor::create([
                            'id' => (string) Str::uuid(),
                            'publication_id' => $pubId,
                            'member_uimp_id' => $authId,
                            'author_order' => $index + 1,
                            'contribution_type' => $authContrib,
                        ]);
                    }
                }
            }

            return redirect()->back()
                ->with('active_section', 'publications')
                ->with('success', 'Publication registered with authors successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('active_section', 'add-records')
                ->with('active_tab', 'add-publication-tab')
                ->withErrors(['error' => $e->getMessage()]);
        }
    });

    Route::post('/publications/{id}/edit', function (Request $request, string $id) {
        $pub = \App\Domain\ResearchGroups\Models\Publication::findOrFail($id);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:500'],
            'publication_type' => ['required', 'string', 'in:Journal-Article,Conference-Paper,Book-Chapter,Technical-Report'],
            'publication_year' => ['required', 'integer'],
            'status' => ['required', 'string', 'in:In-Preparation,Submitted,Under-Review,Accepted,Published,Retracted'],
            'doi' => ['nullable', 'string', 'max:255'],
            'journal_name' => ['nullable', 'string', 'max:300'],
            'publisher' => ['nullable', 'string', 'max:300'],
        ]);

        try {
            $pub->update($data);
            return redirect()->back()
                ->with('active_section', 'publications')
                ->with('success', 'Publication updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('active_section', 'publications')
                ->withErrors(['error' => $e->getMessage()]);
        }
    });

    Route::post('/publications/{id}/delete', function (string $id) {
        $pub = \App\Domain\ResearchGroups\Models\Publication::findOrFail($id);
        try {
            $pub->delete();
            return redirect()->back()
                ->with('active_section', 'publications')
                ->with('success', 'Publication deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('active_section', 'publications')
                ->withErrors(['error' => $e->getMessage()]);
        }
    });

    // ── Publication Authors CRUD ──
    Route::post('/publication-authors/create', function (Request $request) {
        $data = $request->validate([
            'publication_id' => ['required', 'uuid'],
            'member_uimp_id' => ['required', 'string', 'max:100'],
            'author_order' => ['required', 'integer', 'min:1'],
            'contribution_type' => ['required', 'string', 'max:100'],
        ]);

        try {
            \App\Domain\ResearchGroups\Models\PublicationAuthor::create([
                'id' => (string) Str::uuid(),
                'publication_id' => $data['publication_id'],
                'member_uimp_id' => $data['member_uimp_id'],
                'author_order' => $data['author_order'],
                'contribution_type' => $data['contribution_type'],
            ]);
            return redirect()->back()
                ->with('active_section', 'publications')
                ->with('success', 'Author assigned successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('active_section', 'publications')
                ->withErrors(['error' => $e->getMessage()]);
        }
    });

    Route::post('/publication-authors/{id}/delete', function (string $id) {
        $author = \App\Domain\ResearchGroups\Models\PublicationAuthor::findOrFail($id);
        try {
            $author->delete();
            return redirect()->back()
                ->with('active_section', 'publications')
                ->with('success', 'Author removed from publication!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('active_section', 'publications')
                ->withErrors(['error' => $e->getMessage()]);
        }
    });

    Route::post('/publication-authors/{id}/edit', function (Request $request, string $id) {
        $author = \App\Domain\ResearchGroups\Models\PublicationAuthor::findOrFail($id);
        $data = $request->validate([
            'author_order' => ['required', 'integer', 'min:1'],
            'contribution_type' => ['required', 'string', 'max:100'],
        ]);

        try {
            $author->update($data);
            return redirect()->back()
                ->with('active_section', 'publications')
                ->with('success', 'Author details updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('active_section', 'publications')
                ->withErrors(['error' => $e->getMessage()]);
        }
    });

    // ── Group Memberships CRUD ──────────────────────────────────
    Route::post('/group-memberships/create', function (Request $request) {
        $data = $request->validate([
            'group_id' => ['required', 'uuid'],
            'member_uimp_id' => ['required', 'string', 'max:100'],
            'member_type' => ['required', 'string', 'in:Staff,Student,External'],
            'role' => ['required', 'string', 'in:PI,Co-I,Research-Assistant,Graduate-Researcher,External-Collaborator'],
            'workload_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'start_date' => ['required', 'date'],
        ]);

        $data['id'] = (string) Str::uuid();
        $data['status'] = 'Active';
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        try {
            \App\Domain\ResearchGroups\Models\GroupMembership::create($data);
            return redirect()->back()
                ->with('active_section', 'research-groups')
                ->with('success', 'Member added to Research Group successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('active_section', 'add-records')
                ->with('active_tab', 'add-member-tab')
                ->withErrors(['error' => $e->getMessage()]);
        }
    });

    Route::post('/group-memberships/{id}/delete', function (string $id) {
        $membership = \App\Domain\ResearchGroups\Models\GroupMembership::findOrFail($id);
        try {
            $membership->delete();
            return redirect()->back()
                ->with('active_section', 'research-groups')
                ->with('success', 'Member removed from Research Group successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('active_section', 'research-groups')
                ->withErrors(['error' => $e->getMessage()]);
        }
    });

    Route::post('/group-memberships/{id}/edit', function (Request $request, string $id) {
        $membership = \App\Domain\ResearchGroups\Models\GroupMembership::findOrFail($id);
        $data = $request->validate([
            'role' => ['required', 'string', 'in:PI,Co-I,Research-Assistant,Graduate-Researcher,External-Collaborator'],
            'workload_percentage' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        try {
            $membership->update($data);
            return redirect()->back()
                ->with('active_section', 'research-groups')
                ->with('success', 'Member details updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('active_section', 'research-groups')
                ->withErrors(['error' => $e->getMessage()]);
        }
    });
});
