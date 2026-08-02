import { api } from "@/lib/api";

import type {
  Role,
  RoleListResponse,
  RoleQuery,
} from "@/types/role";

function cleanQuery(
  query: RoleQuery
): Record<string, number> {
  return Object.fromEntries(
    Object.entries(query).filter(
      ([, value]) =>
        value !== undefined &&
        value !== null
    )
  ) as Record<string, number>;
}

export const roleService = {
  async getRoles(
    query: RoleQuery = {}
  ): Promise<Role[]> {
    const response =
      await api.get<RoleListResponse>(
        "/api/roles",
        {
          params: cleanQuery(query),
        }
      );

    return response.data.data.roles;
  },
};