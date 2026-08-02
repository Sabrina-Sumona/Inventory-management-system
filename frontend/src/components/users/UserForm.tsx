"use client";

import {
  type FormEvent,
  useState,
} from "react";

import type { Role } from "@/types/role";
import type { CreateUserPayload } from "@/types/user";

interface UserCompanyOption {
  id: number;
  name: string;
  code: string;
}

interface UserFormValues {
  companyId: string;
  name: string;
  email: string;
  password: string;
  passwordConfirmation: string;
  roleIds: number[];
}

interface UserFormProps {
  roles: Role[];
  companies?: UserCompanyOption[];
  isSuperAdmin: boolean;
  isSaving: boolean;
  isLoadingRoles?: boolean;
  errors: Record<string, string>;
  onCompanyChange?: (
    companyId: number | null
  ) => Promise<void> | void;
  onCancel: () => void;
  onSubmit: (
    payload: CreateUserPayload
  ) => Promise<void>;
}

const inputClassName =
  "h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500";

const emptyForm: UserFormValues = {
  companyId: "",
  name: "",
  email: "",
  password: "",
  passwordConfirmation: "",
  roleIds: [],
};

export function UserForm({
  roles,
  companies = [],
  isSuperAdmin,
  isSaving,
  isLoadingRoles = false,
  errors,
  onCompanyChange,
  onCancel,
  onSubmit,
}: UserFormProps) {
  const [form, setForm] =
    useState<UserFormValues>(emptyForm);

  const [
    isPasswordVisible,
    setIsPasswordVisible,
  ] = useState(false);

  const [
    isConfirmationVisible,
    setIsConfirmationVisible,
  ] = useState(false);

  function updateField<
    Key extends keyof UserFormValues,
  >(
    field: Key,
    value: UserFormValues[Key]
  ): void {
    setForm((current) => ({
      ...current,
      [field]: value,
    }));
  }

  async function handleCompanyChange(
    value: string
  ): Promise<void> {
    updateField("companyId", value);
    updateField("roleIds", []);

    const companyId =
      value === "" ? null : Number(value);

    if (
      companyId !== null &&
      (!Number.isInteger(companyId) ||
        companyId <= 0)
    ) {
      return;
    }

    await onCompanyChange?.(companyId);
  }

  function handleRoleChange(
    roleId: number,
    checked: boolean
  ): void {
    setForm((current) => {
      const roleIds = checked
        ? Array.from(
            new Set([
              ...current.roleIds,
              roleId,
            ])
          )
        : current.roleIds.filter(
            (currentRoleId) =>
              currentRoleId !== roleId
          );

      return {
        ...current,
        roleIds,
      };
    });
  }

  async function handleSubmit(
    event: FormEvent<HTMLFormElement>
  ): Promise<void> {
    event.preventDefault();

    const normalizedEmail = form.email
      .trim()
      .toLowerCase();

    const companyId =
      form.companyId === ""
        ? null
        : Number(form.companyId);

    if (
      companyId !== null &&
      (!Number.isInteger(companyId) ||
        companyId <= 0)
    ) {
      return;
    }

    await onSubmit({
      ...(isSuperAdmin
        ? {
            company_id: companyId,
          }
        : {}),

      name: form.name.trim(),
      email: normalizedEmail,
      password: form.password,

      password_confirmation:
        form.passwordConfirmation,

      role_ids: form.roleIds,
    });
  }

  const canSubmit =
    form.name.trim() !== "" &&
    form.email.trim() !== "" &&
    form.password.length >= 8 &&
    form.passwordConfirmation.length >=
      8 &&
    form.password ===
      form.passwordConfirmation &&
    form.roleIds.length > 0 &&
    !isSaving &&
    !isLoadingRoles;

  return (
    <div className="fixed inset-0 z-[70] overflow-y-auto bg-slate-950/60 px-4 py-8">
      <div className="mx-auto w-full max-w-3xl rounded-2xl bg-white shadow-xl">
        <header className="flex items-start justify-between border-b border-slate-200 px-6 py-5">
          <div>
            <h2 className="text-xl font-bold text-slate-950">
              Create user
            </h2>

            <p className="mt-1 text-sm leading-6 text-slate-500">
              Create an internal account and
              assign the appropriate access
              role. Branch and warehouse access
              can be configured afterward.
            </p>
          </div>

          <button
            type="button"
            onClick={onCancel}
            disabled={isSaving}
            aria-label="Close user form"
            className="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
          >
            ✕
          </button>
        </header>

        <form
          onSubmit={handleSubmit}
          className="p-6"
          noValidate
        >
          <div className="space-y-8">
            {isSuperAdmin && (
              <section>
                <h3 className="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">
                  Company access
                </h3>

                <div>
                  <label
                    htmlFor="user-company"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Company
                  </label>

                  <select
                    id="user-company"
                    value={form.companyId}
                    onChange={(event) =>
                      void handleCompanyChange(
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  >
                    <option value="">
                      Global system user
                    </option>

                    {companies.map(
                      (company) => (
                        <option
                          key={company.id}
                          value={company.id}
                        >
                          {company.name} (
                          {company.code})
                        </option>
                      )
                    )}
                  </select>

                  <p className="mt-2 text-xs leading-5 text-slate-500">
                    Global accounts can only
                    receive active global system
                    roles. Select a company to
                    create a company-specific
                    user.
                  </p>

                  {errors.company_id && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.company_id}
                    </p>
                  )}
                </div>
              </section>
            )}

            <section>
              <h3 className="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">
                Account information
              </h3>

              <div className="grid gap-5 md:grid-cols-2">
                <div>
                  <label
                    htmlFor="user-name"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Full name
                  </label>

                  <input
                    id="user-name"
                    name="name"
                    type="text"
                    required
                    maxLength={150}
                    autoComplete="name"
                    value={form.name}
                    onChange={(event) =>
                      updateField(
                        "name",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    aria-invalid={Boolean(
                      errors.name
                    )}
                    className={inputClassName}
                    placeholder="Enter employee name"
                  />

                  {errors.name && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.name}
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="user-email"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Email address
                  </label>

                  <input
                    id="user-email"
                    name="email"
                    type="email"
                    required
                    maxLength={255}
                    autoComplete="email"
                    autoCapitalize="none"
                    spellCheck={false}
                    value={form.email}
                    onChange={(event) =>
                      updateField(
                        "email",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    aria-invalid={Boolean(
                      errors.email
                    )}
                    className={inputClassName}
                    placeholder="employee@deshsolar.com"
                  />

                  {errors.email && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.email}
                    </p>
                  )}
                </div>
              </div>
            </section>

            <section>
              <h3 className="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">
                Initial password
              </h3>

              <div className="grid gap-5 md:grid-cols-2">
                <div>
                  <label
                    htmlFor="user-password"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Password
                  </label>

                  <div className="relative">
                    <input
                      id="user-password"
                      name="password"
                      type={
                        isPasswordVisible
                          ? "text"
                          : "password"
                      }
                      required
                      minLength={8}
                      autoComplete="new-password"
                      value={form.password}
                      onChange={(event) =>
                        updateField(
                          "password",
                          event.target.value
                        )
                      }
                      disabled={isSaving}
                      aria-invalid={Boolean(
                        errors.password
                      )}
                      className={`${inputClassName} pr-20`}
                      placeholder="Create a secure password"
                    />

                    <button
                      type="button"
                      onClick={() =>
                        setIsPasswordVisible(
                          (current) => !current
                        )
                      }
                      disabled={isSaving}
                      className="absolute inset-y-0 right-0 flex items-center px-4 text-sm font-semibold text-slate-500 transition hover:text-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                      {isPasswordVisible
                        ? "Hide"
                        : "Show"}
                    </button>
                  </div>

                  <p className="mt-2 text-xs leading-5 text-slate-500">
                    Use at least 8 characters,
                    including uppercase,
                    lowercase, and numbers.
                  </p>

                  {errors.password && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.password}
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="user-password-confirmation"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Confirm password
                  </label>

                  <div className="relative">
                    <input
                      id="user-password-confirmation"
                      name="password_confirmation"
                      type={
                        isConfirmationVisible
                          ? "text"
                          : "password"
                      }
                      required
                      minLength={8}
                      autoComplete="new-password"
                      value={
                        form.passwordConfirmation
                      }
                      onChange={(event) =>
                        updateField(
                          "passwordConfirmation",
                          event.target.value
                        )
                      }
                      disabled={isSaving}
                      aria-invalid={Boolean(
                        errors.password_confirmation
                      )}
                      className={`${inputClassName} pr-20`}
                      placeholder="Repeat the password"
                    />

                    <button
                      type="button"
                      onClick={() =>
                        setIsConfirmationVisible(
                          (current) => !current
                        )
                      }
                      disabled={isSaving}
                      className="absolute inset-y-0 right-0 flex items-center px-4 text-sm font-semibold text-slate-500 transition hover:text-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                      {isConfirmationVisible
                        ? "Hide"
                        : "Show"}
                    </button>
                  </div>

                  {form.passwordConfirmation !==
                    "" &&
                    form.password !==
                      form.passwordConfirmation && (
                      <p className="mt-2 text-sm text-red-600">
                        Passwords do not match.
                      </p>
                    )}

                  {errors.password_confirmation && (
                    <p className="mt-2 text-sm text-red-600">
                      {
                        errors.password_confirmation
                      }
                    </p>
                  )}
                </div>
              </div>
            </section>

            <section>
              <h3 className="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">
                Role assignment
              </h3>

              {isLoadingRoles ? (
                <div className="rounded-xl border border-slate-200 bg-slate-50 px-5 py-8 text-center text-sm text-slate-500">
                  Loading assignable roles...
                </div>
              ) : roles.length === 0 ? (
                <div className="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm leading-6 text-amber-800">
                  No active assignable roles
                  were found for the selected
                  company.
                </div>
              ) : (
                <div className="grid gap-3 md:grid-cols-2">
                  {roles.map((role) => {
                    const isSelected =
                      form.roleIds.includes(
                        role.id
                      );

                    return (
                      <label
                        key={role.id}
                        className={`flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition ${
                          isSelected
                            ? "border-emerald-500 bg-emerald-50"
                            : "border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50"
                        }`}
                      >
                        <input
                          type="checkbox"
                          checked={isSelected}
                          onChange={(event) =>
                            handleRoleChange(
                              role.id,
                              event.target.checked
                            )
                          }
                          disabled={isSaving}
                          className="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600"
                        />

                        <span className="min-w-0">
                          <span className="block text-sm font-semibold text-slate-900">
                            {role.name}
                          </span>

                          <span className="mt-1 block text-xs font-medium text-slate-500">
                            {role.code}
                          </span>

                          {role.description && (
                            <span className="mt-2 block text-xs leading-5 text-slate-500">
                              {
                                role.description
                              }
                            </span>
                          )}
                        </span>
                      </label>
                    );
                  })}
                </div>
              )}

              {errors.role_ids && (
                <p className="mt-3 text-sm text-red-600">
                  {errors.role_ids}
                </p>
              )}

              {errors["role_ids.0"] && (
                <p className="mt-3 text-sm text-red-600">
                  {errors["role_ids.0"]}
                </p>
              )}
            </section>
          </div>

          <footer className="mt-8 flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
            <button
              type="button"
              onClick={onCancel}
              disabled={isSaving}
              className="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
              Cancel
            </button>

            <button
              type="submit"
              disabled={!canSubmit}
              className="flex min-w-36 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-emerald-400"
            >
              {isSaving && (
                <span
                  aria-hidden="true"
                  className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                />
              )}

              {isSaving
                ? "Creating..."
                : "Create user"}
            </button>
          </footer>
        </form>
      </div>
    </div>
  );
}