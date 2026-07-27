"use client";

import { FormEvent, useState } from "react";
import { AxiosError } from "axios";
import { useRouter } from "next/navigation";

import { authService } from "@/services/authService";
import type { ApiErrorResponse } from "@/types/auth";

interface LoginErrors {
  email?: string;
  password?: string;
  general?: string;
}

export default function LoginPage() {
  const router = useRouter();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [errors, setErrors] = useState<LoginErrors>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function handleSubmit(
    event: FormEvent<HTMLFormElement>
  ): Promise<void> {
    event.preventDefault();

    setErrors({});
    setIsSubmitting(true);

    try {
      await authService.login({
        email,
        password,
      });

      router.replace("/dashboard");
      router.refresh();
    } catch (error) {
      const axiosError =
        error as AxiosError<ApiErrorResponse>;

      const responseData = axiosError.response?.data;
      const validationErrors = responseData?.errors;

      setErrors({
        email: validationErrors?.email?.[0],
        password: validationErrors?.password?.[0],
        general:
          responseData?.message ??
          "Unable to sign in. Please check your credentials.",
      });
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <main className="min-h-screen bg-slate-100">
      <div className="grid min-h-screen lg:grid-cols-2">
        <section className="hidden bg-slate-950 p-12 text-white lg:flex lg:flex-col lg:justify-between">
          <div className="flex items-center gap-3">
            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500 text-lg font-bold text-white">
              DS
            </div>

            <div>
              <p className="text-lg font-semibold">
                Desh Solar
              </p>

              <p className="text-sm text-slate-400">
                Inventory Management System
              </p>
            </div>
          </div>

          <div className="max-w-xl">
            <p className="mb-5 inline-flex rounded-full border border-emerald-400/30 bg-emerald-400/10 px-4 py-2 text-sm font-medium text-emerald-300">
              Secure business operations
            </p>

            <h1 className="text-5xl font-bold leading-tight">
              Manage products, warehouses and inventory
              from one system.
            </h1>

            <p className="mt-6 max-w-lg text-lg leading-8 text-slate-300">
              A centralized platform for Desh Solar
              inventory, procurement, transfers, sales
              and reporting.
            </p>
          </div>

          <p className="text-sm text-slate-500">
            © {new Date().getFullYear()} Desh Solar.
            All rights reserved.
          </p>
        </section>

        <section className="flex items-center justify-center px-5 py-12 sm:px-8">
          <div className="w-full max-w-md">
            <div className="mb-10 flex items-center gap-3 lg:hidden">
              <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-600 font-bold text-white">
                DS
              </div>

              <div>
                <p className="font-semibold text-slate-950">
                  Desh Solar
                </p>

                <p className="text-xs text-slate-500">
                  Inventory Management System
                </p>
              </div>
            </div>

            <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
              <div className="mb-8">
                <h2 className="text-3xl font-bold tracking-tight text-slate-950">
                  Welcome back
                </h2>

                <p className="mt-2 text-sm leading-6 text-slate-500">
                  Sign in with your authorized Desh Solar
                  account.
                </p>
              </div>

              {errors.general && (
                <div
                  role="alert"
                  className="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                >
                  {errors.general}
                </div>
              )}

              <form
                onSubmit={handleSubmit}
                className="space-y-5"
              >
                <div>
                  <label
                    htmlFor="email"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Email address
                  </label>

                  <input
                    id="email"
                    name="email"
                    type="email"
                    autoComplete="email"
                    required
                    value={email}
                    onChange={(event) =>
                      setEmail(event.target.value)
                    }
                    disabled={isSubmitting}
                    placeholder="admin@deshsolar.com"
                    className="h-12 w-full rounded-lg border border-slate-300 bg-white px-4 text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:cursor-not-allowed disabled:bg-slate-100"
                  />

                  {errors.email && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.email}
                    </p>
                  )}
                </div>

                <div>
                  <div className="mb-2 flex items-center justify-between">
                    <label
                      htmlFor="password"
                      className="block text-sm font-medium text-slate-700"
                    >
                      Password
                    </label>

                    <button
                      type="button"
                      className="text-sm font-medium text-emerald-700 hover:text-emerald-800"
                    >
                      Forgot password?
                    </button>
                  </div>

                  <input
                    id="password"
                    name="password"
                    type="password"
                    autoComplete="current-password"
                    required
                    value={password}
                    onChange={(event) =>
                      setPassword(event.target.value)
                    }
                    disabled={isSubmitting}
                    placeholder="Enter your password"
                    className="h-12 w-full rounded-lg border border-slate-300 bg-white px-4 text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:cursor-not-allowed disabled:bg-slate-100"
                  />

                  {errors.password && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.password}
                    </p>
                  )}
                </div>

                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="flex h-12 w-full items-center justify-center rounded-lg bg-emerald-600 px-4 font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-600/20 disabled:cursor-not-allowed disabled:bg-emerald-400"
                >
                  {isSubmitting
                    ? "Signing in..."
                    : "Sign in"}
                </button>
              </form>

              <div className="mt-8 border-t border-slate-200 pt-6">
                <p className="text-center text-xs leading-5 text-slate-500">
                  Access is restricted to authorized Desh
                  Solar employees and administrators.
                </p>
              </div>
            </div>
          </div>
        </section>
      </div>
    </main>
  );
}