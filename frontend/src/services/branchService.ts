import { api } from "@/lib/api";
import type {
  Branch,
  BranchListResponse,
  BranchPayload,
  BranchQuery,
  BranchResponse,
} from "@/types/branch";

function cleanQuery(
  query: BranchQuery
): Record<string, string | number | boolean> {
  return Object.fromEntries(
    Object.entries(query).filter(
      ([, value]) =>
        value !== undefined &&
        value !== null &&
        value !== ""
    )
  ) as Record<string, string | number | boolean>;
}

export const branchService = {
  async getBranches(
    query: BranchQuery = {}
  ): Promise<BranchListResponse["data"]> {
    const response =
      await api.get<BranchListResponse>(
        "/api/branches",
        {
          params: cleanQuery(query),
        }
      );

    return response.data.data;
  },

  async getBranch(
    branchId: number
  ): Promise<Branch> {
    const response =
      await api.get<BranchResponse>(
        `/api/branches/${branchId}`
      );

    return response.data.data.branch;
  },

  async createBranch(
    payload: BranchPayload
  ): Promise<Branch> {
    await api.get("/sanctum/csrf-cookie");

    const response =
      await api.post<BranchResponse>(
        "/api/branches",
        payload
      );

    return response.data.data.branch;
  },

  async updateBranch(
    branchId: number,
    payload: Partial<BranchPayload>
  ): Promise<Branch> {
    await api.get("/sanctum/csrf-cookie");

    const response =
      await api.patch<BranchResponse>(
        `/api/branches/${branchId}`,
        payload
      );

    return response.data.data.branch;
  },

  async deleteBranch(
    branchId: number
  ): Promise<void> {
    await api.get("/sanctum/csrf-cookie");

    await api.delete(
      `/api/branches/${branchId}`
    );
  },

  async restoreBranch(
    branchId: number
  ): Promise<Branch> {
    await api.get("/sanctum/csrf-cookie");

    const response =
      await api.post<BranchResponse>(
        `/api/branches/${branchId}/restore`
      );

    return response.data.data.branch;
  },
};