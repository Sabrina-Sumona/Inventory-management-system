"use client";

import {
  type FormEvent,
  useEffect,
  useState,
} from "react";
import { AxiosError } from "axios";
import Link from "next/link";
import { useRouter } from "next/navigation";

import { useAuth } from "@/contexts/AuthContext";
import type { ApiErrorResponse } from "@/types/auth";

interface LoginErrors {
  email?: string;
  password?: string;
  general?: string;
}

export default function LoginPage() {
  const router = useRouter();

  const {
    login,
    user,
    isLoading: isCheckingAuthentication,
  } = useAuth();

  const [email, setEmail] = useState("");
  const [password, setPassword] =
    useState("");

  const [
    isPasswordVisible,
    setIsPasswordVisible,
  ] = useState(false);

  const [errors, setErrors] =
    useState<LoginErrors>({});

  const [isSubmitting, setIsSubmitting] =
    useState(false);

  useEffect(() => {
    if (
      !isCheckingAuthentication &&
      user
    ) {
      router.replace("/dashboard");
    }
  }, [
    isCheckingAuthentication,
    user,
    router,
  ]);

  function handleEmailChange(
    value: string
  ): void {
    setEmail(value);

    if (errors.email || errors.general) {
      setErrors((current) => ({
        ...current,
        email: undefined,
        general: undefined,
      }));
    }
  }

  function handlePasswordChange(
    value: string
  ): void {
    setPassword(value);

    if (
      errors.password ||
      errors.general
    ) {
      setErrors((current) => ({
        ...current,
        password: undefined,
        general: undefined,
      }));
    }
  }

  async function handleSubmit(
    event: FormEvent<HTMLFormElement>
  ): Promise<void> {
    event.preventDefault();

    const normalizedEmail =
      email.trim().toLowerCase();

    setErrors({});
    setIsSubmitting(true);

    try {
      await login({
        email: normalizedEmail,
        password,
      });

      router.replace("/dashboard");
      router.refresh();
    } catch (error) {
      const axiosError =
        error as AxiosError<ApiErrorResponse>;

      const responseData =
        axiosError.response?.data;

      const validationErrors =
        responseData?.errors;

      setErrors({
        email:
          validationErrors?.email?.[0],

        password:
          validationErrors?.password?.[0],

        general:
          responseData?.message ??
          "Unable to sign in. Please check your email and password.",
      });
    } finally {
      setIsSubmitting(false);
    }
  }

  if (isCheckingAuthentication) {
    return (
      <main className="flex min-h-screen items-center justify-center bg-slate-100 px-5">
        <div
          role="status"
          className="flex flex-col items-center gap-4"
        >
          <div className="h-10 w-10 animate-spin rounded-full border-4 border-slate-300 border-t-emerald-600" />

          <p className="text-sm font-medium text-slate-600">
            Checking authentication...
          </p>
        </div>
      </main>
    );
  }

  return (
    <main className="min-h-screen bg-slate-100">
      <div className="grid min-h-screen lg:grid-cols-2">
        <section className="relative hidden overflow-hidden bg-slate-950 p-12 text-white lg:flex lg:flex-col lg:justify-between">
          <div
            aria-hidden="true"
            className="absolute -left-40 -top-40 h-96 w-96 rounded-full bg-emerald-500/10 blur-3xl"
          />

          <div
            aria-hidden="true"
            className="absolute -bottom-40 -right-40 h-96 w-96 rounded-full bg-blue-500/10 blur-3xl"
          />

          <div className="relative z-10 flex items-center gap-3">
            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500 text-lg font-bold text-white shadow-lg shadow-emerald-950/30">
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

          <div className="relative z-10 max-w-xl">
            <p className="mb-5 inline-flex rounded-full border border-emerald-400/30 bg-emerald-400/10 px-4 py-2 text-sm font-medium text-emerald-300">
              Secure business operations
            </p>

            <h1 className="text-5xl font-bold leading-tight tracking-tight">
              Manage your complete inventory
              operation from one secure
              platform.
            </h1>

            <p className="mt-6 max-w-lg text-lg leading-8 text-slate-300">
              Control suppliers, warehouses,
              procurement, stock movement,
              sales, reporting, and operational
              access across Desh Solar.
            </p>

            <div className="mt-10 grid max-w-lg grid-cols-2 gap-4">
              <div className="rounded-xl border border-white/10 bg-white/5 p-4">
                <p className="text-sm font-semibold text-white">
                  Role-based access
                </p>

                <p className="mt-1 text-xs leading-5 text-slate-400">
                  Users only access authorized
                  companies, branches,
                  warehouses, and modules.
                </p>
              </div>

              <div className="rounded-xl border border-white/10 bg-white/5 p-4">
                <p className="text-sm font-semibold text-white">
                  Centralized management
                </p>

                <p className="mt-1 text-xs leading-5 text-slate-400">
                  Keep operational information
                  organized and available in one
                  system.
                </p>
              </div>
            </div>
          </div>

          <p className="relative z-10 text-sm text-slate-500">
            © {new Date().getFullYear()} Desh
            Solar. All rights reserved.
          </p>
        </section>

        <section className="flex items-center justify-center px-5 py-12 sm:px-8">
          <div className="w-full max-w-md">
            <div className="mb-8 flex items-center gap-3 lg:hidden">
              <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-600 font-bold text-white shadow-sm">
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
                <p className="mb-3 text-sm font-semibold text-emerald-700">
                  Authorized access
                </p>

                <h2 className="text-3xl font-bold tracking-tight text-slate-950">
                  Welcome back
                </h2>

                <p className="mt-2 text-sm leading-6 text-slate-500">
                  Sign in with your authorized
                  Desh Solar account to
                  continue.
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
                noValidate
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
                    autoCapitalize="none"
                    spellCheck={false}
                    required
                    value={email}
                    onChange={(event) =>
                      handleEmailChange(
                        event.target.value
                      )
                    }
                    disabled={isSubmitting}
                    aria-invalid={
                      Boolean(errors.email)
                    }
                    aria-describedby={
                      errors.email
                        ? "email-error"
                        : undefined
                    }
                    placeholder="admin@deshsolar.com"
                    className={`h-12 w-full rounded-lg border bg-white px-4 text-slate-950 outline-none transition placeholder:text-slate-400 focus:ring-4 disabled:cursor-not-allowed disabled:bg-slate-100 ${
                      errors.email
                        ? "border-red-400 focus:border-red-500 focus:ring-red-500/10"
                        : "border-slate-300 focus:border-emerald-600 focus:ring-emerald-600/10"
                    }`}
                  />

                  {errors.email && (
                    <p
                      id="email-error"
                      className="mt-2 text-sm text-red-600"
                    >
                      {errors.email}
                    </p>
                  )}
                </div>

                <div>
                  <div className="mb-2 flex items-center justify-between gap-4">
                    <label
                      htmlFor="password"
                      className="block text-sm font-medium text-slate-700"
                    >
                      Password
                    </label>

                    <Link
                      href="/forgot-password"
                      className="text-sm font-medium text-emerald-700 transition hover:text-emerald-800"
                    >
                      Forgot password?
                    </Link>
                  </div>

                  <div className="relative">
                    <input
                      id="password"
                      name="password"
                      type={
                        isPasswordVisible
                          ? "text"
                          : "password"
                      }
                      autoComplete="current-password"
                      required
                      value={password}
                      onChange={(event) =>
                        handlePasswordChange(
                          event.target.value
                        )
                      }
                      disabled={isSubmitting}
                      aria-invalid={Boolean(
                        errors.password
                      )}
                      aria-describedby={
                        errors.password
                          ? "password-error"
                          : undefined
                      }
                      placeholder="Enter your password"
                      className={`h-12 w-full rounded-lg border bg-white px-4 pr-20 text-slate-950 outline-none transition placeholder:text-slate-400 focus:ring-4 disabled:cursor-not-allowed disabled:bg-slate-100 ${
                        errors.password
                          ? "border-red-400 focus:border-red-500 focus:ring-red-500/10"
                          : "border-slate-300 focus:border-emerald-600 focus:ring-emerald-600/10"
                      }`}
                    />

                    <button
                      type="button"
                      onClick={() =>
                        setIsPasswordVisible(
                          (current) => !current
                        )
                      }
                      disabled={isSubmitting}
                      aria-label={
                        isPasswordVisible
                          ? "Hide password"
                          : "Show password"
                      }
                      className="absolute inset-y-0 right-0 flex items-center px-4 text-sm font-semibold text-slate-500 transition hover:text-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                      {isPasswordVisible
                        ? "Hide"
                        : "Show"}
                    </button>
                  </div>

                  {errors.password && (
                    <p
                      id="password-error"
                      className="mt-2 text-sm text-red-600"
                    >
                      {errors.password}
                    </p>
                  )}
                </div>

                <button
                  type="submit"
                  disabled={
                    isSubmitting ||
                    email.trim() === "" ||
                    password === ""
                  }
                  className="flex h-12 w-full items-center justify-center gap-3 rounded-lg bg-emerald-600 px-4 font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-600/20 disabled:cursor-not-allowed disabled:bg-emerald-400"
                >
                  {isSubmitting && (
                    <span
                      aria-hidden="true"
                      className="h-5 w-5 animate-spin rounded-full border-2 border-white/40 border-t-white"
                    />
                  )}

                  {isSubmitting
                    ? "Signing in..."
                    : "Sign in"}
                </button>
              </form>

              <div className="mt-8 border-t border-slate-200 pt-6">
                <p className="text-center text-xs leading-5 text-slate-500">
                  Access is restricted to
                  authorized Desh Solar
                  employees and administrators.
                  Contact your administrator if
                  you need an account.
                </p>
              </div>
            </div>

            <p className="mt-6 text-center text-xs text-slate-400 lg:hidden">
              © {new Date().getFullYear()} Desh
              Solar
            </p>
          </div>
        </section>
      </div>
    </main>
  );
}