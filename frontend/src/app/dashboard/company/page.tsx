"use client";

import {
  type FormEvent,
  useEffect,
  useState,
} from "react";
import { AxiosError } from "axios";

import { useAuth } from "@/contexts/AuthContext";
import { companyService } from "@/services/companyService";
import type { ApiErrorResponse } from "@/types/auth";
import type {
  Company,
  UpdateCompanyPayload,
} from "@/types/company";

interface CompanyFormValues {
  name: string;
  email: string;
  website: string;
  phone: string;
  address: string;
  timezone: string;
  currency: string;
}

interface SummaryCardProps {
  label: string;
  value: string;
  description: string;
}

function SummaryCard({
  label,
  value,
  description,
}: SummaryCardProps) {
  return (
    <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <p className="text-sm font-medium text-slate-500">
        {label}
      </p>

      <p className="mt-2 text-2xl font-bold text-slate-950">
        {value}
      </p>

      <p className="mt-2 text-sm text-slate-500">
        {description}
      </p>
    </article>
  );
}

function companyToForm(
  company: Company
): CompanyFormValues {
  return {
    name: company.name,
    email: company.email ?? "",
    website: company.website ?? "",
    phone: company.phone ?? "",
    address: company.address ?? "",
    timezone: company.timezone,
    currency: company.currency,
  };
}

function nullableValue(
  value: string
): string | null {
  const trimmedValue = value.trim();

  return trimmedValue.length > 0
    ? trimmedValue
    : null;
}

function getErrorMessage(
  error: unknown,
  fallback: string
): string {
  const axiosError =
    error as AxiosError<ApiErrorResponse>;

  return axiosError.response?.data?.message ??
    fallback;
}

function formatDate(value: string | null): string {
  if (!value) {
    return "Not available";
  }

  return new Intl.DateTimeFormat("en-US", {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(new Date(value));
}

export default function CompanyPage() {
  const {
    user,
    refreshUser,
  } = useAuth();

  const [company, setCompany] =
    useState<Company | null>(null);

  const [form, setForm] =
    useState<CompanyFormValues | null>(null);

  const [fieldErrors, setFieldErrors] =
    useState<Record<string, string>>({});

  const [generalError, setGeneralError] =
    useState<string | null>(null);

  const [successMessage, setSuccessMessage] =
    useState<string | null>(null);

  const [isLoading, setIsLoading] =
    useState(true);

  const [isEditing, setIsEditing] =
    useState(false);

  const [isSaving, setIsSaving] =
    useState(false);

  const canUpdateCompany =
    user?.permissions.some(
      (permission) =>
        permission.code === "company.update"
    ) ?? false;

  useEffect(() => {
    let isActive = true;

    companyService
      .getAccessibleCompanies()
      .then((companies) => {
        if (!isActive) {
          return;
        }

        const accessibleCompany =
          companies[0] ?? null;

        setCompany(accessibleCompany);

        if (accessibleCompany) {
          setForm(
            companyToForm(accessibleCompany)
          );
        } else {
          setGeneralError(
            "No company is assigned to your account."
          );
        }
      })
      .catch((error: unknown) => {
        if (!isActive) {
          return;
        }

        setGeneralError(
          getErrorMessage(
            error,
            "Unable to load company information."
          )
        );
      })
      .finally(() => {
        if (isActive) {
          setIsLoading(false);
        }
      });

    return () => {
      isActive = false;
    };
  }, []);

  function updateField<
    Key extends keyof CompanyFormValues
  >(
    field: Key,
    value: CompanyFormValues[Key]
  ): void {
    setForm((currentForm) => {
      if (!currentForm) {
        return currentForm;
      }

      return {
        ...currentForm,
        [field]: value,
      };
    });

    setFieldErrors((currentErrors) => {
      if (!currentErrors[field]) {
        return currentErrors;
      }

      const nextErrors = {
        ...currentErrors,
      };

      delete nextErrors[field];

      return nextErrors;
    });
  }

  function handleEdit(): void {
    if (!company || !canUpdateCompany) {
      return;
    }

    setForm(companyToForm(company));
    setGeneralError(null);
    setSuccessMessage(null);
    setFieldErrors({});
    setIsEditing(true);
  }

  function handleCancel(): void {
    if (company) {
      setForm(companyToForm(company));
    }

    setFieldErrors({});
    setGeneralError(null);
    setIsEditing(false);
  }

  async function handleSubmit(
    event: FormEvent<HTMLFormElement>
  ): Promise<void> {
    event.preventDefault();

    if (
      !company ||
      !form ||
      !canUpdateCompany
    ) {
      return;
    }

    setIsSaving(true);
    setFieldErrors({});
    setGeneralError(null);
    setSuccessMessage(null);

    const payload: UpdateCompanyPayload = {
      name: form.name.trim(),
      email: nullableValue(form.email),
      website: nullableValue(form.website),
      phone: nullableValue(form.phone),
      address: nullableValue(form.address),
      timezone: form.timezone.trim(),
      currency:
        form.currency.trim().toUpperCase(),
    };

    try {
      const updatedCompany =
        await companyService.updateCompany(
          company.id,
          payload
        );

      setCompany(updatedCompany);
      setForm(companyToForm(updatedCompany));
      setIsEditing(false);

      setSuccessMessage(
        "Company information updated successfully."
      );

      await refreshUser();
    } catch (error) {
      const axiosError =
        error as AxiosError<ApiErrorResponse>;

      const responseData =
        axiosError.response?.data;

      const validationErrors =
        responseData?.errors ?? {};

      const normalizedErrors =
        Object.fromEntries(
          Object.entries(validationErrors).map(
            ([field, messages]) => [
              field,
              messages[0] ??
                "The provided value is invalid.",
            ]
          )
        );

      setFieldErrors(normalizedErrors);

      setGeneralError(
        responseData?.message ??
          "Unable to update company information."
      );
    } finally {
      setIsSaving(false);
    }
  }

  if (isLoading) {
    return (
      <section className="flex min-h-[420px] items-center justify-center rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div className="text-center">
          <div className="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-slate-200 border-t-emerald-600" />

          <p className="mt-4 text-sm text-slate-600">
            Loading company information...
          </p>
        </div>
      </section>
    );
  }

  if (!company || !form) {
    return (
      <section className="rounded-2xl border border-red-200 bg-red-50 p-8">
        <h2 className="text-xl font-bold text-red-900">
          Company unavailable
        </h2>

        <p className="mt-3 text-sm leading-6 text-red-700">
          {generalError ??
            "Company information could not be loaded."}
        </p>

        <button
          type="button"
          onClick={() =>
            window.location.reload()
          }
          className="mt-6 rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-800"
        >
          Try again
        </button>
      </section>
    );
  }

  return (
    <div className="space-y-6">
      <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div className="flex flex-col justify-between gap-5 sm:flex-row sm:items-start">
          <div>
            <div className="flex flex-wrap items-center gap-3">
              <h2 className="text-2xl font-bold text-slate-950">
                {company.name}
              </h2>

              <span
                className={`rounded-full px-3 py-1 text-xs font-semibold ${
                  company.is_active
                    ? "bg-emerald-100 text-emerald-800"
                    : "bg-red-100 text-red-800"
                }`}
              >
                {company.is_active
                  ? "Active"
                  : "Inactive"}
              </span>
            </div>

            <p className="mt-2 text-sm text-slate-500">
              Company code: {company.code}
            </p>

            <p className="mt-1 text-sm text-slate-500">
              Last updated:{" "}
              {formatDate(company.updated_at)}
            </p>
          </div>

          {canUpdateCompany && (
            <div className="flex gap-3">
              {isEditing ? (
                <>
                  <button
                    type="button"
                    onClick={handleCancel}
                    disabled={isSaving}
                    className="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                  >
                    Cancel
                  </button>

                  <button
                    type="submit"
                    form="company-form"
                    disabled={isSaving}
                    className="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-emerald-400"
                  >
                    {isSaving
                      ? "Saving..."
                      : "Save changes"}
                  </button>
                </>
              ) : (
                <button
                  type="button"
                  onClick={handleEdit}
                  className="rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                  Edit company
                </button>
              )}
            </div>
          )}
        </div>
      </section>

      <section className="grid gap-4 sm:grid-cols-3">
        <SummaryCard
          label="Branches"
          value={String(company.branches_count)}
          description="Branches registered under the company"
        />

        <SummaryCard
          label="Warehouses"
          value={String(company.warehouses_count)}
          description="Warehouses registered under the company"
        />

        <SummaryCard
          label="Users"
          value={String(company.users_count)}
          description="Users assigned to the company"
        />
      </section>

      {successMessage && (
        <div
          role="status"
          className="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800"
        >
          {successMessage}
        </div>
      )}

      {generalError && (
        <div
          role="alert"
          className="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700"
        >
          {generalError}
        </div>
      )}

      {!canUpdateCompany && (
        <div className="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
          You have permission to view this company, but
          you do not have permission to update it.
        </div>
      )}

      <form
        id="company-form"
        onSubmit={handleSubmit}
        className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
      >
        <div>
          <h3 className="text-lg font-bold text-slate-950">
            Company information
          </h3>

          <p className="mt-1 text-sm text-slate-500">
            Manage the official details and operating
            configuration of this company.
          </p>
        </div>

        <div className="mt-6 grid gap-5 md:grid-cols-2">
          <div>
            <label
              htmlFor="company-name"
              className="mb-2 block text-sm font-medium text-slate-700"
            >
              Company name
            </label>

            <input
              id="company-name"
              type="text"
              required
              maxLength={255}
              value={form.name}
              onChange={(event) =>
                updateField(
                  "name",
                  event.target.value
                )
              }
              disabled={!isEditing || isSaving}
              className="h-12 w-full rounded-lg border border-slate-300 bg-white px-4 text-slate-950 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:bg-slate-100"
            />

            {fieldErrors.name && (
              <p className="mt-2 text-sm text-red-600">
                {fieldErrors.name}
              </p>
            )}
          </div>

          <div>
            <label
              htmlFor="company-code"
              className="mb-2 block text-sm font-medium text-slate-700"
            >
              Company code
            </label>

            <input
              id="company-code"
              type="text"
              value={company.code}
              readOnly
              className="h-12 w-full cursor-not-allowed rounded-lg border border-slate-300 bg-slate-100 px-4 text-slate-600"
            />

            <p className="mt-2 text-xs text-slate-500">
              The company code is a permanent system
              identifier and cannot be changed.
            </p>
          </div>

          <div>
            <label
              htmlFor="company-email"
              className="mb-2 block text-sm font-medium text-slate-700"
            >
              Email address
            </label>

            <input
              id="company-email"
              type="email"
              maxLength={255}
              value={form.email}
              onChange={(event) =>
                updateField(
                  "email",
                  event.target.value
                )
              }
              disabled={!isEditing || isSaving}
              placeholder="info@deshsolar.com"
              className="h-12 w-full rounded-lg border border-slate-300 bg-white px-4 text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:bg-slate-100"
            />

            {fieldErrors.email && (
              <p className="mt-2 text-sm text-red-600">
                {fieldErrors.email}
              </p>
            )}
          </div>

          <div>
            <label
              htmlFor="company-phone"
              className="mb-2 block text-sm font-medium text-slate-700"
            >
              Phone number
            </label>

            <input
              id="company-phone"
              type="text"
              maxLength={30}
              value={form.phone}
              onChange={(event) =>
                updateField(
                  "phone",
                  event.target.value
                )
              }
              disabled={!isEditing || isSaving}
              placeholder="+880..."
              className="h-12 w-full rounded-lg border border-slate-300 bg-white px-4 text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:bg-slate-100"
            />

            {fieldErrors.phone && (
              <p className="mt-2 text-sm text-red-600">
                {fieldErrors.phone}
              </p>
            )}
          </div>

          <div className="md:col-span-2">
            <label
              htmlFor="company-website"
              className="mb-2 block text-sm font-medium text-slate-700"
            >
              Website
            </label>

            <input
              id="company-website"
              type="url"
              maxLength={255}
              value={form.website}
              onChange={(event) =>
                updateField(
                  "website",
                  event.target.value
                )
              }
              disabled={!isEditing || isSaving}
              placeholder="https://deshsolar.com"
              className="h-12 w-full rounded-lg border border-slate-300 bg-white px-4 text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:bg-slate-100"
            />

            {fieldErrors.website && (
              <p className="mt-2 text-sm text-red-600">
                {fieldErrors.website}
              </p>
            )}
          </div>

          <div>
            <label
              htmlFor="company-timezone"
              className="mb-2 block text-sm font-medium text-slate-700"
            >
              Timezone
            </label>

            <input
              id="company-timezone"
              type="text"
              required
              maxLength={100}
              value={form.timezone}
              onChange={(event) =>
                updateField(
                  "timezone",
                  event.target.value
                )
              }
              disabled={!isEditing || isSaving}
              placeholder="Asia/Dhaka"
              className="h-12 w-full rounded-lg border border-slate-300 bg-white px-4 text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:bg-slate-100"
            />

            {fieldErrors.timezone && (
              <p className="mt-2 text-sm text-red-600">
                {fieldErrors.timezone}
              </p>
            )}
          </div>

          <div>
            <label
              htmlFor="company-currency"
              className="mb-2 block text-sm font-medium text-slate-700"
            >
              Currency code
            </label>

            <input
              id="company-currency"
              type="text"
              required
              minLength={3}
              maxLength={3}
              value={form.currency}
              onChange={(event) =>
                updateField(
                  "currency",
                  event.target.value.toUpperCase()
                )
              }
              disabled={!isEditing || isSaving}
              placeholder="BDT"
              className="h-12 w-full uppercase rounded-lg border border-slate-300 bg-white px-4 text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:bg-slate-100"
            />

            {fieldErrors.currency && (
              <p className="mt-2 text-sm text-red-600">
                {fieldErrors.currency}
              </p>
            )}
          </div>

          <div className="md:col-span-2">
            <label
              htmlFor="company-address"
              className="mb-2 block text-sm font-medium text-slate-700"
            >
              Address
            </label>

            <textarea
              id="company-address"
              rows={4}
              maxLength={2000}
              value={form.address}
              onChange={(event) =>
                updateField(
                  "address",
                  event.target.value
                )
              }
              disabled={!isEditing || isSaving}
              placeholder="Enter the company address"
              className="w-full resize-y rounded-lg border border-slate-300 bg-white px-4 py-3 text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:bg-slate-100"
            />

            {fieldErrors.address && (
              <p className="mt-2 text-sm text-red-600">
                {fieldErrors.address}
              </p>
            )}
          </div>
        </div>
      </form>
    </div>
  );
}