import { api } from "@/lib/api";

import type {
  CreateUserPayload,
  UpdateUserPayload,
  User,
  UserListResponse,
  UserMutationResponse,
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

  async createUser(
    payload: CreateUserPayload
  ): Promise<User> {
    const response =
      await api.post<UserMutationResponse>(
        "/api/users",
        payload
      );

    return response.data.data.user;
  },

  async updateUser(
    userId: number,
    payload: UpdateUserPayload
  ): Promise<User> {
    const response =
      await api.patch<UserMutationResponse>(
        `/api/users/${userId}`,
        payload
      );

    return response.data.data.user;
  },
};