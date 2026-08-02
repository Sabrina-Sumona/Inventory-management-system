"use client";

import {
  type FormEvent,
  useState,
} from "react";
import { AxiosError } from "axios";
import Link from "next/link";

import { authService } from "@/services/authService";
import type { ApiErrorResponse } from "@/types/auth";

export default function ForgotPasswordPage() {
  const [email, setEmail] = useState("");

  const [emailError, setEmailError] =
    useState<string | null>(null);

  const [generalError, setGeneralError] =
    useState<string | null>(null);

  const [
    successMessage,
    setSuccessMessage,
  ] = useState<string | null>(null);

  const [isSubmitting, setIsSubmitting] =
    useState(false);

  function handleEmailChange(
    value: string
  ): void {
    setEmail(value);

    if (emailError) {
      setEmailError(null);
    }

    if (generalError) {
      setGeneralError(null);
    }

    if (successMessage) {
      setSuccessMessage(null);
    }
  }

  async function handleSubmit(
    event: FormEvent<HTMLFormElement>
  ): Promise<void> {
    event.preventDefault();

    const normalizedEmail =
      email.trim().toLowerCase();

    setEmailError(null);
    setGeneralError(null);
    setSuccessMessage(null);
    setIsSubmitting(true);

    try {
      const response =
        await authService.forgotPassword(
          normalizedEmail
        );

      setSuccessMessage(
        response.message ||
          "Password reset instructions have been sent to your email address."
      );
    } catch (error) {
      const axiosError =
        error as AxiosError<ApiErrorResponse>;

      const responseData =
        axiosError.response?.data;

      setEmailError(
        responseData?.errors?.email?.[0] ??
          null
      );

      setGeneralError(
        responseData?.message ??
          "Unable to send the password reset link."
      );
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <main className="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-100 px-5 py-12">
      <div
        aria-hidden="true"
        className="absolute -left-32 -top-32 h-80 w-80 rounded-full bg-emerald-500/10 blur-3xl"
      />

      <div
        aria-hidden="true"
        className="absolute -bottom-32 -right-32 h-80 w-80 rounded-full bg-blue-500/10 blur-3xl"
      />

      <div className="relative z-10 w-full max-w-md">
        <div className="mb-8 text-center">
          <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-600 font-bold text-white shadow-sm">
            DS
          </div>

          <p className="mt-4 text-sm font-semibold text-emerald-700">
            Account recovery
          </p>

          <h1 className="mt-2 text-3xl font-bold tracking-tight text-slate-950">
            Forgot your password?
          </h1>

          <p className="mt-3 text-sm leading-6 text-slate-600">
            Enter your registered email address
            and we will send you instructions to
            reset your password.
          </p>
        </div>

        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
          {successMessage && (
            <div
              role="status"
              className="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm leading-6 text-emerald-800"
            >
              {successMessage}
            </div>
          )}

          {generalError && (
            <div
              role="alert"
              className="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm leading-6 text-red-700"
            >
              {generalError}
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
                required
                autoComplete="email"
                autoCapitalize="none"
                spellCheck={false}
                value={email}
                onChange={(event) =>
                  handleEmailChange(
                    event.target.value
                  )
                }
                disabled={isSubmitting}
                aria-invalid={Boolean(
                  emailError
                )}
                aria-describedby={
                  emailError
                    ? "email-error"
                    : undefined
                }
                placeholder="admin@deshsolar.com"
                className={`h-12 w-full rounded-lg border bg-white px-4 text-slate-950 outline-none transition placeholder:text-slate-400 focus:ring-4 disabled:cursor-not-allowed disabled:bg-slate-100 ${
                  emailError
                    ? "border-red-400 focus:border-red-500 focus:ring-red-500/10"
                    : "border-slate-300 focus:border-emerald-600 focus:ring-emerald-600/10"
                }`}
              />

              {emailError && (
                <p
                  id="email-error"
                  className="mt-2 text-sm text-red-600"
                >
                  {emailError}
                </p>
              )}
            </div>

            <button
              type="submit"
              disabled={
                isSubmitting ||
                email.trim() === ""
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
                ? "Sending..."
                : "Send reset link"}
            </button>
          </form>

          <div className="mt-6 border-t border-slate-200 pt-6 text-center">
            <Link
              href="/login"
              className="text-sm font-semibold text-emerald-700 transition hover:text-emerald-800"
            >
              Back to sign in
            </Link>
          </div>
        </div>

        <p className="mt-6 text-center text-xs text-slate-400">
          © {new Date().getFullYear()} Desh
          Solar. Authorized users only.
        </p>
      </div>
    </main>
  );
}