import { api } from "@/lib/api";

import type {
  SyncBranchAssignmentsPayload,
  SyncWarehouseAssignmentsPayload,
  UserAssignmentResponse,
  UserAssignments,
} from "@/types/userAssignment";

export const userAssignmentService = {
  async getAssignments(
    userId: number
  ): Promise<UserAssignments> {
    const response =
      await api.get<UserAssignmentResponse>(
        `/api/users/${userId}/assignments`
      );

    return response.data.data.user;
  },

  async syncBranchAssignments(
    userId: number,
    payload: SyncBranchAssignmentsPayload
  ): Promise<UserAssignments> {
    await api.get("/sanctum/csrf-cookie");

    const response =
      await api.put<UserAssignmentResponse>(
        `/api/users/${userId}/branch-assignments`,
        payload
      );

    return response.data.data.user;
  },

  async syncWarehouseAssignments(
    userId: number,
    payload: SyncWarehouseAssignmentsPayload
  ): Promise<UserAssignments> {
    await api.get("/sanctum/csrf-cookie");

    const response =
      await api.put<UserAssignmentResponse>(
        `/api/users/${userId}/warehouse-assignments`,
        payload
      );

    return response.data.data.user;
  },
};