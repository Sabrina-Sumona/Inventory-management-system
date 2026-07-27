import { api } from "@/lib/api";
import type {
  AuthUser,
  CurrentUserResponse,
  LoginCredentials,
  LoginResponse,
} from "@/types/auth";

export const authService = {
  async login(
    credentials: LoginCredentials
  ): Promise<LoginResponse> {
    await api.get("/sanctum/csrf-cookie");

    const response = await api.post<LoginResponse>(
      "/api/auth/login",
      credentials
    );

    return response.data;
  },

  async getCurrentUser(): Promise<AuthUser> {
    const response = await api.get<CurrentUserResponse>(
      "/api/auth/user"
    );

    return response.data.data.user;
  },

  async logout(): Promise<void> {
    await api.get("/sanctum/csrf-cookie");
    await api.post("/api/auth/logout");
  },
};