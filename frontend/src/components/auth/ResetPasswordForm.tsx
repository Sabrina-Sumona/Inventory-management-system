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
  const [email, setEmail] =
    useState(initialEmail);

  const [password, setPassword] =
    useState("");

  const [
    passwordConfirmation,
    setPasswordConfirmation,
  ] = useState("");

  const [
    isPasswordVisible,
    setIsPasswordVisible,
  ] = useState(false);

  const [
    isConfirmationVisible,
    setIsConfirmationVisible,
  ] = useState(false);

  const [errors, setErrors] =
    useState<FormErrors>({});

  const [
    successMessage,
    setSuccessMessage,
  ] = useState<string | null>(null);

  const [isSubmitting, setIsSubmitting] =
    useState(false);

  const hasToken =
    initialToken.trim().length > 0;

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
      errors.passwordConfirmation ||
      errors.general
    ) {
      setErrors((current) => ({
        ...current,
        password: undefined,
        passwordConfirmation:
          undefined,
        general: undefined,
      }));
    }
  }

  function handleConfirmationChange(
    value: string
  ): void {
    setPasswordConfirmation(value);

    if (
      errors.passwordConfirmation ||
      errors.general
    ) {
      setErrors((current) => ({
        ...current,
        passwordConfirmation:
          undefined,
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
    setSuccessMessage(null);

    if (!hasToken) {
      setErrors({
        general:
          "The password reset token is missing. Please request a new reset link.",
      });

      return;
    }

    if (password.length < 8) {
      setErrors({
        password:
          "The password must be at least 8 characters.",
      });

      return;
    }

    if (
      password !==
      passwordConfirmation
    ) {
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
          email: normalizedEmail,
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

      const responseData =
        axiosError.response?.data;

      const validationErrors =
        responseData?.errors;

      setErrors({
        email:
          validationErrors?.email?.[0],

        password:
          validationErrors?.password?.[0],

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
      <main className="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-100 px-5 py-12">
        <div
          aria-hidden="true"
          className="absolute -left-32 -top-32 h-80 w-80 rounded-full bg-emerald-500/10 blur-3xl"
        />

        <div
          aria-hidden="true"
          className="absolute -bottom-32 -right-32 h-80 w-80 rounded-full bg-blue-500/10 blur-3xl"
        />

        <div className="relative z-10 w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
          <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-3xl font-bold text-emerald-700">
            ✓
          </div>

          <p className="mt-5 text-sm font-semibold text-emerald-700">
            Account recovery complete
          </p>

          <h1 className="mt-2 text-2xl font-bold tracking-tight text-slate-950">
            Password reset complete
          </h1>

          <p className="mt-3 text-sm leading-6 text-slate-600">
            {successMessage}
          </p>

          <Link
            href="/login"
            className="mt-7 inline-flex h-12 w-full items-center justify-center rounded-lg bg-emerald-600 px-4 font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-600/20"
          >
            Continue to sign in
          </Link>

          <p className="mt-6 text-xs text-slate-400">
            © {new Date().getFullYear()}{" "}
            Desh Solar
          </p>
        </div>
      </main>
    );
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
            Secure password reset
          </p>

          <h1 className="mt-2 text-3xl font-bold tracking-tight text-slate-950">
            Create a new password
          </h1>

          <p className="mt-3 text-sm leading-6 text-slate-600">
            Enter your account email and
            choose a secure new password.
          </p>
        </div>

        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
          {!hasToken && (
            <div
              role="alert"
              className="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm leading-6 text-red-700"
            >
              The password reset token is
              missing. Please request a new
              reset link.
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
                  errors.email
                )}
                aria-describedby={
                  errors.email
                    ? "reset-email-error"
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
                  id="reset-email-error"
                  className="mt-2 text-sm text-red-600"
                >
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

              <div className="relative">
                <input
                  id="password"
                  name="password"
                  type={
                    isPasswordVisible
                      ? "text"
                      : "password"
                  }
                  required
                  minLength={8}
                  autoComplete="new-password"
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
                      ? "reset-password-error"
                      : "reset-password-help"
                  }
                  placeholder="Enter a new password"
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

              {errors.password ? (
                <p
                  id="reset-password-error"
                  className="mt-2 text-sm text-red-600"
                >
                  {errors.password}
                </p>
              ) : (
                <p
                  id="reset-password-help"
                  className="mt-2 text-xs leading-5 text-slate-500"
                >
                  Use at least 8 characters.
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

              <div className="relative">
                <input
                  id="passwordConfirmation"
                  name="password_confirmation"
                  type={
                    isConfirmationVisible
                      ? "text"
                      : "password"
                  }
                  required
                  minLength={8}
                  autoComplete="new-password"
                  value={passwordConfirmation}
                  onChange={(event) =>
                    handleConfirmationChange(
                      event.target.value
                    )
                  }
                  disabled={isSubmitting}
                  aria-invalid={Boolean(
                    errors.passwordConfirmation
                  )}
                  aria-describedby={
                    errors.passwordConfirmation
                      ? "reset-password-confirmation-error"
                      : undefined
                  }
                  placeholder="Confirm the new password"
                  className={`h-12 w-full rounded-lg border bg-white px-4 pr-20 text-slate-950 outline-none transition placeholder:text-slate-400 focus:ring-4 disabled:cursor-not-allowed disabled:bg-slate-100 ${
                    errors.passwordConfirmation
                      ? "border-red-400 focus:border-red-500 focus:ring-red-500/10"
                      : "border-slate-300 focus:border-emerald-600 focus:ring-emerald-600/10"
                  }`}
                />

                <button
                  type="button"
                  onClick={() =>
                    setIsConfirmationVisible(
                      (current) => !current
                    )
                  }
                  disabled={isSubmitting}
                  aria-label={
                    isConfirmationVisible
                      ? "Hide password confirmation"
                      : "Show password confirmation"
                  }
                  className="absolute inset-y-0 right-0 flex items-center px-4 text-sm font-semibold text-slate-500 transition hover:text-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  {isConfirmationVisible
                    ? "Hide"
                    : "Show"}
                </button>
              </div>

              {errors.passwordConfirmation && (
                <p
                  id="reset-password-confirmation-error"
                  className="mt-2 text-sm text-red-600"
                >
                  {
                    errors.passwordConfirmation
                  }
                </p>
              )}
            </div>

            <button
              type="submit"
              disabled={
                isSubmitting ||
                !hasToken ||
                email.trim() === "" ||
                password === "" ||
                passwordConfirmation === ""
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
                ? "Resetting password..."
                : "Reset password"}
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