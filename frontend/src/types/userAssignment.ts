export interface AssignmentCompany {
  id: number;
  name: string;
  code: string;
}

export interface AssignedBranch {
  id: number;
  name: string;
  code: string;
  city: string | null;
  district: string | null;
  is_head_office: boolean;
  is_active: boolean;
  is_primary: boolean;
}

export interface AssignmentWarehouseBranch {
  id: number;
  name: string;
  code: string;
}

export interface AssignedWarehouse {
  id: number;
  name: string;
  code: string;
  city: string | null;
  district: string | null;
  is_primary: boolean;
  is_active: boolean;
  branch: AssignmentWarehouseBranch;
}

export interface UserAssignments {
  id: number;
  name: string;
  email: string;
  company: AssignmentCompany | null;
  branches: AssignedBranch[];
  warehouses: AssignedWarehouse[];
}

export interface UserAssignmentResponse {
  success: boolean;
  message: string;
  data: {
    user: UserAssignments;
  };
}

export interface SyncBranchAssignmentsPayload {
  branch_ids: number[];
  primary_branch_id: number | null;
}

export interface SyncWarehouseAssignmentsPayload {
  warehouse_ids: number[];
  primary_warehouse_id: number | null;
}