import { api } from "@/lib/api";

import type {
  User,
  UserListResponse,
  UserQuery,
  UserResponse,
} from "@/types/user";

function cleanQuery(
  query: UserQuery
): Record<string, string | number> {
  return Object.fromEntries(
    Object.entries(query).filter(
      ([, value]) =>
        value !== undefined &&
        value !== null &&
        value !== ""
    )
  ) as Record<string, string | number>;
}

export const userService = {
  async getUsers(
    query: UserQuery = {}
  ): Promise<UserListResponse["data"]> {
    const response =
      await api.get<UserListResponse>(
        "/api/users",
        {
          params: cleanQuery(query),
        }
      );

    return response.data.data;
  },

  async getUser(
    userId: number
  ): Promise<User> {
    const response =
      await api.get<UserResponse>(
        `/api/users/${userId}`
      );

    return response.data.data.user;
  },
};