import { api } from "@/lib/api";
import type {
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
};