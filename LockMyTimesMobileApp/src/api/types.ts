export interface NotificationPreference {
  in_app: boolean;
  email: boolean;
}

export interface UserProfile {
  id: number;
  employee_id: number | null;
  name: string;
  email: string;
  phone: string | null;
  avatar_url: string;
  locale: string;
  timezone: string | null;
  date_format: string;
  time_format: string;
  theme: string;
  must_change_password: boolean;
  notification_preferences: Record<string, NotificationPreference>;
  last_login_at?: string | null;
  last_login_ip?: string | null;
}

export interface TenantInfo {
  company_name: string;
  logo_url: string;
  status: 'trial' | 'active' | 'suspended' | 'cancelled';
}

export interface LoginResponse {
  token: string;
  user: UserProfile;
  must_change_password: boolean;
}

export interface ApiErrorBody {
  message: string;
  errors?: Record<string, string[]>;
  status?: string;
  must_change_password?: boolean;
}

export interface LocationInfo {
  id: number;
  name: string;
  code: string | null;
  latitude: number | null;
  longitude: number | null;
  geofence_radius_meters: number | null;
  is_headquarters: boolean;
}

export interface AttendanceBreakInfo {
  id: number;
  break_type: string;
  start_at: string | null;
  end_at: string | null;
  duration_minutes: number;
  is_paid: boolean;
  notes: string | null;
}

export interface AttendanceRecord {
  id: number;
  work_date: string;
  status: string;
  clock_in_at: string | null;
  clock_out_at: string | null;
  total_hours: number;
  regular_hours: number;
  overtime_hours: number;
  break_hours: number;
  is_late: boolean;
  late_minutes: number;
  is_early_out: boolean;
  early_minutes: number;
  is_geofence_breach: boolean;
  source: string | null;
  notes: string | null;
  location: LocationInfo | null;
  breaks: AttendanceBreakInfo[];
}

export type ClockStatus = 'not_clocked_in' | 'clocked_in' | 'on_break' | 'clocked_out';

export interface ShiftInfo {
  name: string;
  start: string;
  end: string;
  label: string;
}

export interface AttendanceDay {
  date: string;
  is_weekend: boolean;
  holiday_name: string | null;
  status_key: string;
  attendance: AttendanceRecord | null;
}

export interface AttendanceSummary {
  present_days: number;
  absent_days: number;
  half_days: number;
  leave_days: number;
  late_count: number;
  early_out: number;
  total_hours: number;
  regular_hours: number;
  overtime_hours: number;
  break_hours: number;
}

export interface AttendanceIndexResponse {
  month: string;
  days: AttendanceDay[];
  summary: AttendanceSummary;
  today: {
    clock_status: ClockStatus;
    attendance: AttendanceRecord | null;
    shift: ShiftInfo | null;
  };
  assigned_locations: LocationInfo[];
}

export interface AttendanceStatusResponse {
  status: ClockStatus;
  clock_in_at: string | null;
  clock_out_at: string | null;
  is_late: boolean;
  late_minutes: number;
  worked_minutes: number;
  break_started: string | null;
}

export interface AttendanceDayResponse {
  date: string;
  attendance: AttendanceRecord | null;
  holiday_name: string | null;
  shift: ShiftInfo | null;
}

export interface LeaveType {
  id: number;
  name: string;
  color: string;
  is_paid: boolean;
  default_days_per_year: number;
  requires_approval: boolean;
  requires_documentation: boolean;
  max_consecutive_days: number | null;
  notice_days_required: number | null;
}

export interface LeaveBalanceInfo {
  id: number;
  type_id: number;
  name: string;
  color: string;
  is_paid: boolean;
  total: number;
  used: number;
  pending: number;
  available: number;
  used_pct: number;
}

export type LeaveStatus = 'pending' | 'approved' | 'rejected' | 'cancelled';

export interface LeaveAttachment {
  name: string | null;
  url: string | null;
  size: number | null;
}

export interface RequesterInfo {
  id: number;
  full_name: string;
  avatar_url: string;
  position: string | null;
}

export interface LeaveRequestInfo {
  id: number;
  request_number: string;
  employee?: RequesterInfo;
  leave_type: LeaveType;
  start_date: string;
  end_date: string;
  total_days: number;
  day_part: string;
  reason: string;
  contact_during_leave: string | null;
  attachments: LeaveAttachment[];
  status: LeaveStatus;
  approver_name: string | null;
  approved_at: string | null;
  approver_comments: string | null;
  rejection_reason: string | null;
  created_at: string;
}

export interface LeaveIndexResponse {
  year: number;
  balances: LeaveBalanceInfo[];
  summary: { available: number; used: number; pending: number; total: number };
  requests: LeaveRequestInfo[];
  pagination: { current_page: number; last_page: number; total: number };
  leave_types: LeaveType[];
  years: number[];
}

export interface LeaveCalculateResponse {
  workdays: number;
  total: number;
  available: number | null;
  remaining: number | null;
  holidays: { date: string; name: string }[];
  warnings: string[];
}

export interface PayslipItemInfo {
  id: number;
  label: string;
  type: string;
  amount: number;
}

export type PayslipStatus = 'draft' | 'finalized' | 'paid' | 'cancelled';

export interface PayslipLineItem {
  label: string;
  amount: number;
}

export interface PayslipNavRef {
  id: number;
  payslip_number: string;
}

export interface PayslipInfo {
  id: number;
  payslip_number: string;
  period_start: string;
  period_end: string;
  pay_date: string;
  status: PayslipStatus;
  payment_method: string | null;
  pay_schedule?: string | null;
  employee?: { full_name: string; employee_code: string | null; position: string | null; department: string | null };
  regular_hours: number;
  overtime_hours: number;
  holiday_hours: number;
  leave_hours: number;
  earnings: PayslipLineItem[];
  deduction_breakdown: PayslipLineItem[];
  gross_pay: number;
  net_pay: number;
  total_deductions: number;
  tax_amount: number;
  ytd_gross: number;
  ytd_net: number;
  ytd_taxes: number;
  items: PayslipItemInfo[];
  prev?: PayslipNavRef | null;
  next?: PayslipNavRef | null;
}

export interface EmergencyContactInfo {
  id: number;
  name: string;
  relationship: string;
  phone: string;
  email: string | null;
  address: string | null;
  is_primary: boolean;
}

export interface EmployeeProfile {
  id: number;
  employee_code: string | null;
  full_name: string;
  preferred_name: string | null;
  avatar_url: string;
  email: string;
  personal_email: string | null;
  phone: string | null;
  mobile: string | null;
  date_of_birth: string | null;
  gender: string | null;
  marital_status: string | null;
  nationality: string | null;
  address: {
    line1: string | null;
    line2: string | null;
    city: string | null;
    state: string | null;
    postal_code: string | null;
    country: string | null;
  };
  department: string | null;
  position: string | null;
  location: string | null;
  manager_name: string | null;
  hire_date: string | null;
  probation_end_date: string | null;
  confirmation_date: string | null;
  employment_status: string | null;
  employment_type: string | null;
  privacy_settings: Record<string, boolean>;
}

export interface ProfileIndexResponse {
  employee: EmployeeProfile;
  emergency_contacts: EmergencyContactInfo[];
}

export interface NotificationInfo {
  id: string;
  type: string;
  title: string;
  icon: string | null;
  color: string | null;
  action_url: string | null;
  data: Record<string, unknown>;
  is_read: boolean;
  read_at: string | null;
  created_at: string;
}

export interface NotificationsIndexResponse {
  notifications: NotificationInfo[];
  unread_count: number;
  pagination: { current_page: number; last_page: number; total: number };
}

export interface ProjectInfo {
  id: number;
  code: string;
  name: string;
  description: string | null;
  color: string;
  status: string;
  priority: string;
  progress: number;
  start_date: string | null;
  due_date: string | null;
  is_overdue: boolean;
  manager_name: string | null;
  my_total?: number;
  my_open?: number;
  my_overdue?: number;
  my_role?: string | null;
}

export interface TaskAssigneeInfo {
  id: number;
  employee_id: number;
  name: string;
  avatar_url: string;
  is_primary: boolean;
}

export interface TaskChecklistInfo {
  id: number;
  item: string;
  is_completed: boolean;
  completed_at: string | null;
  sort_order: number;
}

export interface TaskCommentInfo {
  id: number;
  content: string;
  user_name: string;
  user_avatar_url: string;
  is_edited: boolean;
  created_at: string;
}

export interface TaskAttachmentInfo {
  id: number;
  file_name: string;
  mime_type: string;
  file_size: number;
  file_size_human: string;
  is_image: boolean;
  url: string;
}

export type TaskStatus = 'backlog' | 'todo' | 'in_progress' | 'in_review' | 'on_hold' | 'done' | 'cancelled';
export type TaskPriority = 'low' | 'normal' | 'high' | 'urgent';

export interface TaskInfo {
  id: number;
  project_id: number;
  task_list_id: number;
  parent_task_id: number | null;
  task_code: string;
  title: string;
  description: string | null;
  type: string;
  status: TaskStatus;
  priority: TaskPriority;
  progress: number;
  due_date: string | null;
  completed_at: string | null;
  estimated_hours: number;
  is_overdue: boolean;
  comments_count: number;
  subtasks_count: number;
  completed_subtasks_count: number;
  created_by: number;
  sort_order: number;
  project?: ProjectInfo;
  task_list?: { id: number; name: string; color: string | null };
  assignees: TaskAssigneeInfo[];
  checklists?: TaskChecklistInfo[];
  comments?: TaskCommentInfo[];
  attachments?: TaskAttachmentInfo[];
}

export interface TaskListInfo {
  id: number;
  name: string;
  color: string | null;
  column_type: string;
  sort_order: number;
  wip_limit: number | null;
  is_closed_status: boolean;
  tasks: TaskInfo[];
}

export interface ProjectMemberInfo {
  id: number;
  employee_id: number;
  name: string;
  avatar_url: string;
  role: string;
}

export interface TasksIndexResponse {
  tasks: TaskInfo[];
  pagination: { current_page: number; last_page: number; total: number };
  counters: {
    total: number;
    open_count: number;
    todo: number;
    in_progress: number;
    in_review: number;
    on_hold: number;
    done: number;
    overdue: number;
  };
  created_count: number;
  my_projects: ProjectInfo[];
  today_due: number;
  this_week_due: number;
}

export interface ProjectBoardResponse {
  project: ProjectInfo;
  is_manager: boolean;
  task_lists: TaskListInfo[];
  members: ProjectMemberInfo[];
  task_stats: { done: number; in_progress: number; overdue: number };
}

export interface TaskShowResponse {
  task: TaskInfo;
  can_edit: boolean;
  checklist_total: number;
  checklist_done: number;
  checklist_pct: number;
}

export interface ExpenseCategoryInfo {
  id: number;
  name: string;
  color: string;
  requires_receipt: boolean;
  max_amount: number | null;
  per_expense_limit: number | null;
  receipt_required_above: number | null;
}

export type ExpenseStatus = 'draft' | 'submitted' | 'approved' | 'rejected' | 'paid' | 'cancelled';

export interface TimelineEvent {
  icon: string;
  color: string;
  title: string;
  detail: string | null;
  when: string;
}

export interface ExpenseApprovalInfo {
  level: number;
  approver_name: string | null;
  decision: string;
  decided_at: string | null;
  comments: string | null;
}

export interface ExpenseInfo {
  id: number;
  expense_number: string;
  employee?: RequesterInfo;
  category: ExpenseCategoryInfo;
  title: string;
  description: string | null;
  amount: number;
  currency: string;
  expense_date: string;
  merchant: string | null;
  payment_method: string | null;
  project_code: string | null;
  receipt_url: string | null;
  is_mileage: boolean;
  miles: number | null;
  mileage_rate: number | null;
  status: ExpenseStatus;
  submitted_at: string | null;
  approved_at: string | null;
  paid_at: string | null;
  rejection_reason: string | null;
  created_at: string;
  timeline?: TimelineEvent[];
  approvals?: ExpenseApprovalInfo[];
}

export interface ExpensesIndexResponse {
  year: number;
  years: number[];
  expenses: ExpenseInfo[];
  pagination: { current_page: number; last_page: number; total: number };
  totals: {
    pending_amount: number;
    approved_amount: number;
    paid_amount: number;
    rejected_amount: number;
    reimbursable_amount: number;
  };
  counters: {
    total: number;
    draft: number;
    submitted: number;
    approved: number;
    rejected: number;
    paid: number;
    cancelled: number;
  };
  by_category: { category: ExpenseCategoryInfo; total: number; count: number }[];
  categories: ExpenseCategoryInfo[];
}

export interface LoanTypeInfo {
  id: number;
  name: string;
  color: string;
  default_interest_rate: number;
  max_amount: number | null;
  min_amount: number | null;
  max_tenure_months: number | null;
  min_tenure_months: number | null;
  min_service_months: number | null;
  min_salary: number | null;
  max_salary_multiplier: number | null;
  requires_guarantor: boolean;
  is_eligible?: boolean;
  block_reasons?: string[];
  effective_max?: number | null;
}

export interface LoanRepaymentInfo {
  id: number;
  installment_number: number;
  due_date: string;
  emi_amount: number;
  principal_component: number;
  interest_component: number;
  balance_after: number;
  paid_date: string | null;
  amount_paid: number;
  status: string;
  is_overdue: boolean;
}

export interface LoanInfo {
  id: number;
  loan_number: string;
  loan_type: LoanTypeInfo;
  principal_amount: number;
  interest_rate: number;
  interest_type: string | null;
  total_interest: number;
  processing_fee: number;
  total_amount: number;
  tenure_months: number;
  emi_amount: number;
  first_emi_date: string;
  amount_paid: number;
  amount_remaining: number;
  installments_paid: number;
  installments_remaining: number;
  purpose: string;
  guarantor_name: string | null;
  guarantor_phone: string | null;
  auto_deduct_from_payroll: boolean;
  status: string;
  approver_comments: string | null;
  rejection_reason: string | null;
  disbursement_method: string | null;
  disbursement_reference: string | null;
  requested_at: string | null;
  approved_at: string | null;
  disbursed_at: string | null;
  completed_at: string | null;
  progress_pct: number;
  repayments?: LoanRepaymentInfo[];
  timeline?: TimelineEvent[];
}

export interface AdvanceDeductionInfo {
  id: number;
  deduction_number: string;
  deduction_date: string;
  amount: number;
  status: string;
}

export interface SalaryAdvanceInfo {
  id: number;
  advance_number: string;
  created_at: string;
  amount: number;
  reason: string;
  repayment_type: string;
  installments_count: number;
  per_installment_amount: number;
  first_deduction_date: string;
  amount_repaid: number;
  amount_remaining: number;
  installments_paid: number;
  status: string;
  approver_comments: string | null;
  rejection_reason: string | null;
  approved_at: string | null;
  disbursed_at: string | null;
  completed_at: string | null;
  progress_pct: number;
  deductions?: AdvanceDeductionInfo[];
  timeline?: TimelineEvent[];
}

export interface LoansIndexResponse {
  loans: LoanInfo[];
  loans_pagination: { current_page: number; last_page: number; total: number };
  loan_stats: {
    total: number;
    pending: number;
    active: number;
    completed: number;
    total_outstanding: number;
    total_paid: number;
    total_borrowed: number;
  };
  active_loan: LoanInfo | null;
  next_emi: { due_date: string; emi_amount: number; installment_number: number } | null;
  advances: SalaryAdvanceInfo[];
  advances_pagination: { current_page: number; last_page: number; total: number };
  advance_stats: { total: number; pending: number; active: number; completed: number; total_outstanding: number };
}

export interface TeamMemberInfo {
  id: number;
  full_name: string;
  avatar_url: string;
  position: string | null;
  department: string | null;
  status_today: 'on_leave' | 'clocked_in' | 'absent';
  pending_leaves: number;
  pending_expenses: number;
  open_tasks: number;
}

export interface TeamIndexResponse {
  is_manager: boolean;
  reports?: TeamMemberInfo[];
  pending_leaves?: number;
  pending_expenses?: number;
  headcount?: number;
  on_leave_count?: number;
  clocked_in?: number;
  recent_leaves?: LeaveRequestInfo[];
  recent_expenses?: ExpenseInfo[];
}

export interface TeamMemberDetailResponse {
  member: EmployeeProfile;
  leaves: LeaveRequestInfo[];
  expenses: ExpenseInfo[];
  open_tasks: TaskInfo[];
  recent_attendance: AttendanceRecord[];
  leave_balances: { name: string; color: string; total: number; available: number }[];
  is_on_leave_today: boolean;
  today_attendance: AttendanceRecord | null;
  pending_leaves: number;
  pending_expenses: number;
}

export interface AnnouncementInfo {
  id: number;
  title: string;
  content: string;
  priority: string;
  requires_acknowledgment: boolean;
  banner_image_url: string | null;
  creator_name: string;
  published_at: string | null;
  expires_at: string | null;
  views_count: number;
  is_read?: boolean;
  is_acknowledged?: boolean;
  acknowledged_at?: string | null;
  needs_action?: boolean;
}

export interface AnnouncementsIndexResponse {
  announcements: AnnouncementInfo[];
  pagination: { current_page: number; last_page: number; total: number };
  counters: { total: number; unread: number; needs_ack: number; active_polls: number };
  active_polls: PollInfo[];
}

export interface PollInfo {
  id: number;
  question: string;
  description: string | null;
  type: string;
  is_anonymous: boolean;
  options: string[];
  status: string;
  starts_at: string | null;
  ends_at: string | null;
  creator_name: string;
  has_voted?: boolean;
  my_choices?: number[];
  is_active?: boolean;
}

export interface PollResultsResponse {
  poll: PollInfo;
  total_votes: number;
  results: { index: number; option: string; votes: number; percent: number }[];
  show_results: boolean;
}

export interface EmiCalculation {
  emi: number;
  total_amount: number;
  total_interest: number;
}

export interface PayslipIndexResponse {
  year: number;
  years: number[];
  payslips: PayslipInfo[];
  pagination: { current_page: number; last_page: number; total: number };
  counters: { total: number; draft: number; finalized: number; paid: number; cancelled: number };
  latest: PayslipInfo | null;
  ytd: {
    gross: number;
    net: number;
    taxes: number;
    deductions: number;
    regular_hours: number;
    overtime_hours: number;
    count: number;
  };
  monthly: { month: string; net: number }[];
}
