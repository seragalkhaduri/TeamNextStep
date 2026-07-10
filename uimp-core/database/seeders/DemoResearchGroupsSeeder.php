<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Domain\ResearchGroups\Models\ResearchGroup;
use App\Domain\ResearchGroups\Models\GroupMembership;
use App\Domain\ResearchGroups\Models\FundingSource;
use App\Domain\ResearchGroups\Models\ResearchProject;
use App\Domain\ResearchGroups\Models\Publication;
use App\Domain\ResearchGroups\Models\PublicationAuthor;
use App\Domain\Employees\Models\Employee;
use App\Domain\Auth\Models\User;

class DemoResearchGroupsSeeder extends Seeder
{
    public function run(): void
    {
        // Get user for created_by / updated_by
        $sysadmin = User::where('username', 'sysadmin')->first();
        $creatorId = $sysadmin ? $sysadmin->id : Str::uuid()->toString();

        // Get some employees to use as PI and members
        $employees = Employee::all();
        if ($employees->isEmpty()) {
            return;
        }

        $pi1 = $employees->first();
        $pi2 = $employees->skip(1)->first() ?? $pi1;
        $member1 = $employees->skip(2)->first() ?? $pi1;

        // Group 1: AI & Data Science
        $group1 = ResearchGroup::create([
            'id' => (string) Str::uuid(),
            'group_name' => 'Artificial Intelligence & Data Science Group',
            'research_field' => 'Computer Science & Engineering',
            'research_area' => 'Machine Learning, Deep Learning, Natural Language Processing',
            'status' => 'Active',
            'pi_staff_id' => $pi1->institutional_id,
            'department_ref_id' => '019f42d7-f474-72b3-aad9-764d4b41c6a1', // Computer Science
            'budget_allocation' => 150000.00,
            'created_by' => $creatorId,
            'updated_by' => $creatorId,
        ]);

        // Group 2: Advanced Renewable Energy
        $group2 = ResearchGroup::create([
            'id' => (string) Str::uuid(),
            'group_name' => 'Renewable Energy Technologies',
            'research_field' => 'Electrical & Power Engineering',
            'research_area' => 'Solar Power, Wind Turbines, Smart Grid Integration',
            'status' => 'Active',
            'pi_staff_id' => $pi2->institutional_id,
            'department_ref_id' => '019f42d7-f47b-727f-b704-3e4b25400824', // Electrical Engineering
            'budget_allocation' => 200000.00,
            'created_by' => $creatorId,
            'updated_by' => $creatorId,
        ]);

        // Seed Funding Sources
        $funding1 = FundingSource::create([
            'id' => (string) Str::uuid(),
            'research_group_id' => $group1->id,
            'agency_name' => 'National Science Foundation (NSF)',
            'grant_reference' => 'GR-NSF-2026-CS88',
            'allocated_amount' => 120000.00,
            'currency_code' => 'LYD',
            'start_date' => '2026-01-01',
            'end_date' => '2027-12-31',
            'status' => 'Active',
            'notes' => 'Funding for AI & Deep Learning compute clusters.',
            'created_by' => $creatorId,
            'updated_by' => $creatorId,
        ]);

        // Link Group 1 to funding source
        $group1->update(['funding_source_id' => $funding1->id]);

        $funding2 = FundingSource::create([
            'id' => (string) Str::uuid(),
            'research_group_id' => $group2->id,
            'agency_name' => 'Ministry of Higher Education and Research',
            'grant_reference' => 'GR-MOHER-2026-RE22',
            'allocated_amount' => 180000.00,
            'currency_code' => 'LYD',
            'start_date' => '2026-02-01',
            'end_date' => '2028-02-01',
            'status' => 'Active',
            'notes' => 'Renewable energy integration in dry climates.',
            'created_by' => $creatorId,
            'updated_by' => $creatorId,
        ]);

        // Link Group 2 to funding source
        $group2->update(['funding_source_id' => $funding2->id]);

        // Seed Group Memberships
        GroupMembership::create([
            'id' => (string) Str::uuid(),
            'group_id' => $group1->id,
            'member_uimp_id' => $pi1->institutional_id,
            'member_type' => 'Staff',
            'role' => 'PI',
            'start_date' => '2026-01-01',
            'workload_percentage' => 40,
            'status' => 'Active',
            'created_by' => $creatorId,
            'updated_by' => $creatorId,
        ]);

        GroupMembership::create([
            'id' => (string) Str::uuid(),
            'group_id' => $group1->id,
            'member_uimp_id' => $member1->institutional_id,
            'member_type' => 'Staff',
            'role' => 'Co-I',
            'start_date' => '2026-01-15',
            'workload_percentage' => 30,
            'status' => 'Active',
            'created_by' => $creatorId,
            'updated_by' => $creatorId,
        ]);

        GroupMembership::create([
            'id' => (string) Str::uuid(),
            'group_id' => $group2->id,
            'member_uimp_id' => $pi2->institutional_id,
            'member_type' => 'Staff',
            'role' => 'PI',
            'start_date' => '2026-02-01',
            'workload_percentage' => 50,
            'status' => 'Active',
            'created_by' => $creatorId,
            'updated_by' => $creatorId,
        ]);

        // Student members in Group 1 (AI)
        GroupMembership::create([
            'id' => (string) Str::uuid(),
            'group_id' => $group1->id,
            'member_uimp_id' => 'STU-2024-001001',
            'member_type' => 'Student',
            'role' => 'Graduate-Researcher',
            'start_date' => '2026-02-01',
            'workload_percentage' => 25,
            'status' => 'Active',
            'created_by' => $creatorId,
            'updated_by' => $creatorId,
        ]);

        GroupMembership::create([
            'id' => (string) Str::uuid(),
            'group_id' => $group1->id,
            'member_uimp_id' => 'STU-2024-001002',
            'member_type' => 'Student',
            'role' => 'Graduate-Researcher',
            'start_date' => '2026-02-10',
            'workload_percentage' => 20,
            'status' => 'Active',
            'created_by' => $creatorId,
            'updated_by' => $creatorId,
        ]);

        // Student members in Group 2 (Energy)
        GroupMembership::create([
            'id' => (string) Str::uuid(),
            'group_id' => $group2->id,
            'member_uimp_id' => 'STU-2023-000501',
            'member_type' => 'Student',
            'role' => 'Research-Assistant',
            'start_date' => '2026-03-01',
            'workload_percentage' => 30,
            'status' => 'Active',
            'created_by' => $creatorId,
            'updated_by' => $creatorId,
        ]);

        // Seed Projects
        ResearchProject::create([
            'id' => (string) Str::uuid(),
            'title' => 'NLP Frameworks for Arabic Dialects Classification',
            'research_group_id' => $group1->id,
            'funding_agency' => 'National Science Foundation (NSF)',
            'budget' => 75000.00,
            'start_date' => '2026-01-10',
            'end_date' => '2027-01-10',
            'status' => 'Active',
            'risk_status' => 'Low',
            'compliance_status' => 'Compliant',
            'risk_description' => 'None identified so far.',
            'mitigation_actions' => 'Regular progress audits.',
            'created_by' => $creatorId,
            'updated_by' => $creatorId,
        ]);

        ResearchProject::create([
            'id' => (string) Str::uuid(),
            'title' => 'Optimizing Solar Harvesting in Desert Microgrids',
            'research_group_id' => $group2->id,
            'funding_agency' => 'Ministry of Higher Education and Research',
            'budget' => 120000.00,
            'start_date' => '2026-03-01',
            'end_date' => '2027-09-01',
            'status' => 'Active',
            'risk_status' => 'Medium',
            'compliance_status' => 'Compliant',
            'risk_description' => 'Possible dust storms reducing panel efficiency during measurements.',
            'mitigation_actions' => 'Automated cleaning system trial.',
            'created_by' => $creatorId,
            'updated_by' => $creatorId,
        ]);

        // Seed Publications
        $pub1 = Publication::create([
            'id' => (string) Str::uuid(),
            'research_group_id' => $group1->id,
            'title' => 'Deep Learning Models for Libyan Dialect Analysis',
            'publication_type' => 'Journal-Article',
            'publication_year' => 2026,
            'status' => 'Published',
            'doi' => '10.1007/s11042-026-12345-x',
            'journal_name' => 'Journal of Computational Linguistics',
            'issn' => '0891-2017',
            'publisher' => 'MIT Press',
            'impact_factor' => 4.250,
            'citation_count' => 12,
            'created_by' => $creatorId,
            'updated_by' => $creatorId,
        ]);

        PublicationAuthor::create([
            'id' => (string) Str::uuid(),
            'publication_id' => $pub1->id,
            'member_uimp_id' => $pi1->institutional_id,
            'author_order' => 1,
            'contribution_type' => 'Lead Researcher / Algorithm Development',
        ]);
    }
}
