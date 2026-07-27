"use client";

import {
  type FormEvent,
  useState,
} from "react";
import { AxiosError } from "axios";
import Link from "next/link";

import { authService } from "@/services/authService";
import type { ApiErrorResponse } from "@/types/auth";

interface ResetPasswordFormProps {
  initialToken: string;
  initialEmail: string;
}

interface FormErrors {
  email?: string;
  password?: string;
  passwordConfirmation?: string;
  general?: string;
}

export function ResetPasswordForm({
  initialToken,
  initialEmail,
}: ResetPasswordFormProps) {
  const [email, setEmail] = useState(initialEmail);
  const [password, setPassword] = useState("");
  const [
    passwordConfirmation,
    setPasswordConfirmation,
  ] = useState("");

  const [errors, setErrors] =
    useState<FormErrors>({});

  const [successMessage, setSuccessMessage] =
    useState<string | null>(null);

  const [isSubmitting, setIsSubmitting] =
    useState(false);

  const hasToken = initialToken.length > 0;

  async function handleSubmit(
    event: FormEvent<HTMLFormElement>
  ): Promise<void> {
    event.preventDefault();

    setErrors({});
    setSuccessMessage(null);

    if (!hasToken) {
      setErrors({
        general:
          "The password reset token is missing. Please request a new reset link.",
      });

      return;
    }

    if (password !== passwordConfirmation) {
      setErrors({
        passwordConfirmation:
          "The password confirmation does not match.",
      });

      return;
    }

    setIsSubmitting(true);

    try {
      const response =
        await authService.resetPassword({
          token: initialToken,
          email,
          password,
          password_confirmation:
            passwordConfirmation,
        });

      setSuccessMessage(
        response.message ||
          "Your password has been reset successfully."
      );

      setPassword("");
      setPasswordConfirmation("");
    } catch (error) {
      const axiosError =
        error as AxiosError<ApiErrorResponse>;

      const responseData = axiosError.response?.data;
      const validationErrors = responseData?.errors;

      setErrors({
        email: validationErrors?.email?.[0],
        password: validationErrors?.password?.[0],
        passwordConfirmation:
          validationErrors
            ?.password_confirmation?.[0],
        general:
          responseData?.message ??
          "Unable to reset your password. The link may be invalid or expired.",
      });
    } finally {
      setIsSubmitting(false);
    }
  }

  if (successMessage) {
    return (
      <main className="flex min-h-screen items-center justify-center bg-slate-100 px-5 py-12">
        <div className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
          <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-2xl text-emerald-700">
            ✓
          </div>

          <h1 className="mt-6 text-2xl font-bold text-slate-950">
            Password reset complete
          </h1>

          <p className="mt-3 text-sm leading-6 text-slate-600">
            {successMessage}
          </p>

          <Link
            href="/login"
            className="mt-7 inline-flex h-12 w-full items-center justify-center rounded-lg bg-emerald-600 px-4 font-semibold text-white transition hover:bg-emerald-700"
          >
            Continue to sign in
          </Link>
        </div>
      </main>
    );
  }

  return (
    <main className="flex min-h-screen items-center justify-center bg-slate-100 px-5 py-12">
      <div className="w-full max-w-md">
        <div className="mb-8 text-center">
          <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-600 font-bold text-white">
            DS
          </div>

          <h1 className="mt-5 text-3xl font-bold text-slate-950">
            Create a new password
          </h1>

          <p className="mt-3 text-sm leading-6 text-slate-600">
            Enter your email address and choose a new
            secure password.
          </p>
        </div>

        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
          {!hasToken && (
            <div
              role="alert"
              className="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm leading-6 text-red-700"
            >
              The password reset token is missing. Please
              request another reset link.
            </div>
          )}

          {errors.general && (
            <div
              role="alert"
              className="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm leading-6 text-red-700"
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
                required
                autoComplete="email"
                value={email}
                onChange={(event) =>
                  setEmail(event.target.value)
                }
                disabled={isSubmitting}
                className="h-12 w-full rounded-lg border border-slate-300 bg-white px-4 text-slate-950 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:bg-slate-100"
              />

              {errors.email && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.email}
                </p>
              )}
            </div>

            <div>
              <label
                htmlFor="password"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                New password
              </label>

              <input
                id="password"
                name="password"
                type="password"
                required
                minLength={8}
                autoComplete="new-password"
                value={password}
                onChange={(event) =>
                  setPassword(event.target.value)
                }
                disabled={isSubmitting}
                placeholder="Enter a new password"
                className="h-12 w-full rounded-lg border border-slate-300 bg-white px-4 text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:bg-slate-100"
              />

              {errors.password && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.password}
                </p>
              )}
            </div>

            <div>
              <label
                htmlFor="passwordConfirmation"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                Confirm new password
              </label>

              <input
                id="passwordConfirmation"
                name="password_confirmation"
                type="password"
                required
                minLength={8}
                autoComplete="new-password"
                value={passwordConfirmation}
                onChange={(event) =>
                  setPasswordConfirmation(
                    event.target.value
                  )
                }
                disabled={isSubmitting}
                placeholder="Confirm the new password"
                className="h-12 w-full rounded-lg border border-slate-300 bg-white px-4 text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:bg-slate-100"
              />

              {errors.passwordConfirmation && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.passwordConfirmation}
                </p>
              )}
            </div>

            <button
              type="submit"
              disabled={isSubmitting || !hasToken}
              className="flex h-12 w-full items-center justify-center rounded-lg bg-emerald-600 px-4 font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-emerald-400"
            >
              {isSubmitting
                ? "Resetting password..."
                : "Reset password"}
            </button>
          </form>

          <div className="mt-6 text-center">
            <Link
              href="/login"
              className="text-sm font-semibold text-emerald-700 hover:text-emerald-800"
            >
              Back to sign in
            </Link>
          </div>
        </div>
      </div>
    </main>
  );
}