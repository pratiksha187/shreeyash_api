<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ErpGapAnalysisController extends Controller
{
    public function index(): View
    {
        $modules = $this->modules();

        return view('admin.erp-gap-analysis.index', [
            'modules' => $modules,
            'summary' => [
                'implemented' => collect($modules)->where('status', 'implemented')->count(),
                'partial' => collect($modules)->where('status', 'partial')->count(),
                'missing' => collect($modules)->where('status', 'missing')->count(),
                'total' => count($modules),
            ],
            'phases' => $this->phases(),
            'demoWorkflow' => $this->demoWorkflow(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function modules(): array
    {
        return [
            [
                'name' => 'Project Management',
                'document_scope' => 'Projects, phases, tasks, schedules, budgets, work orders, BOQ and planned vs actual tracking.',
                'current_project' => 'Project master, tasks, due dates, assignees, progress, status, employee performance, work order references, BOQ/SOR item tracking, planned/executed quantities, planned/actual cost and cost-to-complete controls exist.',
                'gap' => 'Advanced phase/sub-phase hierarchy, formal work-order approval documents and deep SOR rate-analysis libraries can still be expanded later.',
                'status' => 'implemented',
                'priority' => 'High',
            ],
            [
                'name' => 'Planning & Estimation',
                'document_scope' => 'Resource planning, material estimation, budget limits and project/activity/material budget vs actual.',
                'current_project' => 'Project budget amount and task planning exist.',
                'gap' => 'Detailed estimation, resource baselines, material templates and variance alerts are missing.',
                'status' => 'missing',
                'priority' => 'High',
            ],
            [
                'name' => 'Purchase Management',
                'document_scope' => 'Material requisition, indent, vendor enquiry, quotations, comparison, PO, approval and GRN.',
                'current_project' => 'Product purchase, challan entry, material requests and a purchase workflow register for requisition, indent, vendor enquiry, quotation comparison, PO approval limits and GRN status exist.',
                'gap' => 'Formal approval documents, automatic GRN stock posting and deep vendor rate-analysis reports can still be expanded later.',
                'status' => 'partial',
                'priority' => 'High',
            ],
            [
                'name' => 'Store & Inventory',
                'document_scope' => 'Material master, stock, receipts, movement, issues, consumption and reconciliation.',
                'current_project' => 'Material master, stock movements, material requests and issues exist.',
                'gap' => 'Opening + receipt - issue/consumption - return = closing reconciliation and project/activity consumption linkage should be strengthened.',
                'status' => 'partial',
                'priority' => 'High',
            ],
            [
                'name' => 'Machinery & Vehicle Management',
                'document_scope' => 'Equipment, logs, service, breakdown, fuel, idle status, job cards and cost per hour.',
                'current_project' => 'Vehicles, vehicle logs, driver attendance, daily diesel purchase and machinery diesel logs exist.',
                'gap' => 'Service schedules, breakdown/idle time, job cards, repair cost and depreciation reports are missing.',
                'status' => 'partial',
                'priority' => 'Medium',
            ],
            [
                'name' => 'Labour Management',
                'document_scope' => 'Permanent and daily-wage labour, attendance, shifts, overtime, category, work allocation and muster.',
                'current_project' => 'Labour master, contractor master, site master, labour attendance, photos, time fields, work-category allocation, wage rates, overtime rates and muster payroll costing exist.',
                'gap' => 'Advanced statutory payroll exports and deeper productivity analysis can be expanded later.',
                'status' => 'partial',
                'priority' => 'Medium',
            ],
            [
                'name' => 'HR & Payroll',
                'document_scope' => 'Employees, attendance, leave, overtime, advances, reimbursements, salary processing and deductions.',
                'current_project' => 'Employees, attendance, leave approval, payments and payslip PDF generation exist.',
                'gap' => 'Full payroll, statutory deductions, loans/advances and reimbursement workflow are missing.',
                'status' => 'partial',
                'priority' => 'Medium',
            ],
            [
                'name' => 'Contractor Management',
                'document_scope' => 'Contractors, agreements, contracts, progress, renewals, measurements and RA billing.',
                'current_project' => 'Contractor master exists in labour attendance.',
                'gap' => 'Work orders, measurements, RA bills, retention, recovery, TDS, GST and net payable workflow are missing.',
                'status' => 'missing',
                'priority' => 'High',
            ],
            [
                'name' => 'Finance & Accounts',
                'document_scope' => 'Ledgers, AP/AR, vouchers, budgets, bank/cash, payments, assets and project P&L.',
                'current_project' => 'Employee payment generation and slips exist.',
                'gap' => 'General ledger, vouchers, AP/AR, bank reconciliation, budget accounting and project-wise P&L are missing.',
                'status' => 'missing',
                'priority' => 'High',
            ],
            [
                'name' => 'GST / Statutory',
                'document_scope' => 'GST returns, e-invoice, e-way bill, TDS, vendor reconciliation and auditor exports.',
                'current_project' => 'No complete statutory finance module found.',
                'gap' => 'GST, TDS, e-invoice, e-way bill and CA-friendly export workflows are missing.',
                'status' => 'missing',
                'priority' => 'High',
            ],
            [
                'name' => 'MIS & Management Reporting',
                'document_scope' => 'Project, purchase, HR, machinery, finance and director dashboard KPIs.',
                'current_project' => 'Admin dashboard and module reports/exports exist in several areas.',
                'gap' => 'Unified director dashboard, budget variance, contractor payable, material value and project P&L need more depth.',
                'status' => 'partial',
                'priority' => 'High',
            ],
            [
                'name' => 'Approval System',
                'document_scope' => 'Role-based maker-checker approval/rejection with monetary limits.',
                'current_project' => 'Admin permissions and leave/material request approvals exist.',
                'gap' => 'Configurable approval hierarchy, value bands and transaction audit trail are missing.',
                'status' => 'partial',
                'priority' => 'High',
            ],
            [
                'name' => 'Multi-company & Multi-site',
                'document_scope' => 'Company, site, module and user-role access controls.',
                'current_project' => 'Company subscriptions, tenant database handling and role/module permissions exist.',
                'gap' => 'Site-level access restrictions can be made more granular.',
                'status' => 'implemented',
                'priority' => 'Medium',
            ],
            [
                'name' => 'Mobile Operations',
                'document_scope' => 'DPR, attendance, receipt/issue, approvals, machinery logs and attachments from phone.',
                'current_project' => 'API controllers exist for attendance, DPR, payments, complaints, challans, materials and project tasks.',
                'gap' => 'Offline-first queue/sync and mobile approval coverage are missing.',
                'status' => 'partial',
                'priority' => 'High',
            ],
            [
                'name' => 'Reporting & Exports',
                'document_scope' => 'Excel, CSV, PDF, scheduled reports, custom report builder, BI connector and API access.',
                'current_project' => 'Exports exist for selected admin reports; PDFs exist for challans, payments and site reports.',
                'gap' => 'Scheduled reporting, custom report builder, BI connector and report permission matrix are missing.',
                'status' => 'partial',
                'priority' => 'Medium',
            ],
            [
                'name' => 'Security & Audit',
                'document_scope' => 'MFA, encryption, audit logs, backups, recovery, RPO/RTO, data export and deletion.',
                'current_project' => 'Login, route permissions and tenant isolation exist.',
                'gap' => 'MFA, full audit logs, backup retention screen, DR policy tracking and data exit tooling are missing.',
                'status' => 'missing',
                'priority' => 'High',
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function phases(): array
    {
        return [
            ['phase' => 'Phase 1', 'modules' => 'Projects + Purchase + Store + Approvals', 'goal' => 'Control material and procurement flow.'],
            ['phase' => 'Phase 2', 'modules' => 'Machinery + Labour + Contractors', 'goal' => 'Control site resources and subcontractors.'],
            ['phase' => 'Phase 3', 'modules' => 'Accounts + Billing + GST', 'goal' => 'Integrate commercial and finance.'],
            ['phase' => 'Phase 4', 'modules' => 'HR + Payroll', 'goal' => 'Centralize employee and wage processes.'],
            ['phase' => 'Phase 5', 'modules' => 'MIS + Director Dashboard', 'goal' => 'Management visibility and KPI control.'],
            ['phase' => 'Phase 6', 'modules' => 'Advanced automation / integrations', 'goal' => 'BI, APIs, external systems and optimization.'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function demoWorkflow(): array
    {
        return [
            'BOQ',
            'Project Budget',
            'Site Activity',
            'DPR',
            'Material Requirement',
            'Indent',
            'Vendor Quotations',
            'Comparison',
            'PO',
            'GRN',
            'Material Issue',
            'Consumption',
            'Machinery Hours',
            'Diesel',
            'Labour Attendance',
            'Subcontractor Measurement',
            'RA Bill',
            'Client Bill',
            'Payment Requisition',
            'Vendor Payment',
            'Project P&L',
            'Budget vs Actual',
            'Management Dashboard',
        ];
    }
}
