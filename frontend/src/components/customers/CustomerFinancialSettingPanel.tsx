"use client";

import {
  useEffect,
  useState,
} from "react";
import { AxiosError } from "axios";

import { CustomerFinancialSettingForm } from "@/components/customers/CustomerFinancialSettingForm";
import { customerService } from "@/services/customerService";
import type { ApiErrorResponse } from "@/types/auth";
import type {
  Customer,
  CustomerFinancialSetting,
  CustomerFinancialSettingPayload,
} from "@/types/customer";

interface CustomerFinancialSettingPanelProps {
  customer: Customer;
  canCreate: boolean;
  canUpdate: boolean;
  canDelete: boolean;
  onClose: () => void;
}

function getApiMessage(
  error: unknown,
  fallback: string
): string {
  const axiosError =
    error as AxiosError<ApiErrorResponse>;

  return (
    axiosError.response?.data?.message ??
    fallback
  );
}

function getValidationErrors(
  error: unknown
): Record<string, string> {
  const axiosError =
    error as AxiosError<ApiErrorResponse>;

  const errors =
    axiosError.response?.data?.errors ?? {};

  return Object.fromEntries(
    Object.entries(errors).map(
      ([field, messages]) => [
        field,
        messages[0] ??
          "The provided value is invalid.",
      ]
    )
  );
}

function formatMoney(
  value: string | number,
  currencyCode: string
): string {
  const numericValue = Number(value);

  if (!Number.isFinite(numericValue)) {
    return `${currencyCode} 0.00`;
  }

  try {
    return new Intl.NumberFormat("en-BD", {
      style: "currency",
      currency: currencyCode,
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(numericValue);
  } catch {
    return `${currencyCode} ${numericValue.toFixed(
      2
    )}`;
  }
}

function formatLabel(
  value: string
): string {
  return value
    .split("_")
    .map(
      (word) =>
        word.charAt(0).toUpperCase() +
        word.slice(1)
    )
    .join(" ");
}

export function CustomerFinancialSettingPanel({
  customer,
  canCreate,
  canUpdate,
  canDelete,
  onClose,
}: CustomerFinancialSettingPanelProps) {
  const [
    financialSetting,
    setFinancialSetting,
  ] =
    useState<CustomerFinancialSetting | null>(
      null
    );

  const [isLoading, setIsLoading] =
    useState(true);

  const [isSaving, setIsSaving] =
    useState(false);

  const [isDeleting, setIsDeleting] =
    useState(false);

  const [isFormOpen, setIsFormOpen] =
    useState(false);

  const [formErrors, setFormErrors] =
    useState<Record<string, string>>({});

  const [errorMessage, setErrorMessage] =
    useState<string | null>(null);

  const [
    successMessage,
    setSuccessMessage,
  ] = useState<string | null>(null);

  useEffect(() => {
    let isMounted = true;

    customerService
      .getCustomerFinancialSettings({
        customer_id: customer.id,
        page: 1,
        per_page: 1,
      })
      .then((data) => {
        if (!isMounted) {
          return;
        }

        setFinancialSetting(
          data.customer_financial_settings[0] ??
            null
        );
      })
      .catch((error: unknown) => {
        if (!isMounted) {
          return;
        }

        setErrorMessage(
          getApiMessage(
            error,
            "Unable to load customer financial settings."
          )
        );
      })
      .finally(() => {
        if (isMounted) {
          setIsLoading(false);
        }
      });

    return () => {
      isMounted = false;
    };
  }, [customer.id]);

  function openCreateForm(): void {
    setFormErrors({});
    setErrorMessage(null);
    setSuccessMessage(null);
    setIsFormOpen(true);
  }

  function openEditForm(): void {
    if (!financialSetting) {
      return;
    }

    setFormErrors({});
    setErrorMessage(null);
    setSuccessMessage(null);
    setIsFormOpen(true);
  }

  function closeForm(): void {
    if (isSaving) {
      return;
    }

    setIsFormOpen(false);
    setFormErrors({});
  }

  async function handleSave(
    payload: CustomerFinancialSettingPayload
  ): Promise<void> {
    setIsSaving(true);
    setFormErrors({});
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      let savedSetting:
        CustomerFinancialSetting;

      if (financialSetting) {
        savedSetting =
          await customerService.updateCustomerFinancialSetting(
            financialSetting.id,
            {
              currency_code:
                payload.currency_code,

              default_payment_method:
                payload.default_payment_method,

              payment_term_days:
                payload.payment_term_days,

              credit_limit:
                payload.credit_limit,

              allow_credit_sale:
                payload.allow_credit_sale,

              block_sale_on_credit_limit:
                payload.block_sale_on_credit_limit,

              default_sales_discount_percent:
                payload.default_sales_discount_percent,

              is_tax_applicable:
                payload.is_tax_applicable,

              default_tax_percent:
                payload.default_tax_percent,

              is_withholding_tax_applicable:
                payload.is_withholding_tax_applicable,

              withholding_tax_percent:
                payload.withholding_tax_percent,

              sales_price_basis:
                payload.sales_price_basis,

              default_sales_order_term:
                payload.default_sales_order_term,

              payment_instruction:
                payload.payment_instruction,

              notes:
                payload.notes,

              is_active:
                payload.is_active,
            }
          );

        setSuccessMessage(
          "Customer financial settings updated successfully."
        );
      } else {
        savedSetting =
          await customerService.createCustomerFinancialSetting(
            payload
          );

        setSuccessMessage(
          "Customer financial settings created successfully."
        );
      }

      setFinancialSetting(savedSetting);
      setIsFormOpen(false);
    } catch (error) {
      setFormErrors(
        getValidationErrors(error)
      );

      setErrorMessage(
        getApiMessage(
          error,
          "Unable to save customer financial settings."
        )
      );
    } finally {
      setIsSaving(false);
    }
  }

  async function handleDelete(): Promise<void> {
    if (!financialSetting) {
      return;
    }

    const confirmed = window.confirm(
      `Delete financial settings for "${customer.name}"?`
    );

    if (!confirmed) {
      return;
    }

    setIsDeleting(true);
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      await customerService.deleteCustomerFinancialSetting(
        financialSetting.id
      );

      setFinancialSetting(null);

      setSuccessMessage(
        "Customer financial settings deleted successfully."
      );
    } catch (error) {
      setErrorMessage(
        getApiMessage(
          error,
          "Unable to delete customer financial settings."
        )
      );
    } finally {
      setIsDeleting(false);
    }
  }

  const isBusy =
    isSaving || isDeleting;

  return (
    <>
      <div className="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/60 p-4">
        <div className="max-h-[94vh] w-full max-w-5xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
          <div className="flex flex-col justify-between gap-4 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center">
            <div>
              <h2 className="text-xl font-bold text-slate-950">
                Customer Financial Settings
              </h2>

              <p className="mt-1 text-sm text-slate-500">
                {customer.name} ·{" "}
                {customer.code}
              </p>
            </div>

            <div className="flex flex-wrap gap-3">
              {!isLoading &&
                !financialSetting &&
                canCreate && (
                  <button
                    type="button"
                    onClick={openCreateForm}
                    disabled={isBusy}
                    className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-emerald-400"
                  >
                    Create settings
                  </button>
                )}

              {!isLoading &&
                financialSetting &&
                canUpdate && (
                  <button
                    type="button"
                    onClick={openEditForm}
                    disabled={isBusy}
                    className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-emerald-400"
                  >
                    Edit settings
                  </button>
                )}

              <button
                type="button"
                onClick={onClose}
                disabled={isBusy}
                className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
              >
                Close
              </button>
            </div>
          </div>

          <div className="space-y-5 p-6">
            {successMessage && (
              <div
                role="status"
                className="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800"
              >
                {successMessage}
              </div>
            )}

            {errorMessage && (
              <div
                role="alert"
                className="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700"
              >
                {errorMessage}
              </div>
            )}

            {isLoading ? (
              <div className="rounded-xl border border-slate-200 px-6 py-16 text-center text-sm text-slate-500">
                Loading customer financial
                settings...
              </div>
            ) : !financialSetting ? (
              <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-16 text-center">
                <h3 className="text-base font-bold text-slate-900">
                  No financial settings found
                </h3>

                <p className="mx-auto mt-2 max-w-xl text-sm text-slate-500">
                  This customer currently uses
                  only the basic payment terms
                  stored in the customer profile.
                </p>

                {canCreate ? (
                  <button
                    type="button"
                    onClick={openCreateForm}
                    className="mt-5 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700"
                  >
                    Create financial settings
                  </button>
                ) : (
                  <p className="mt-4 text-xs font-medium text-slate-400">
                    You do not have permission
                    to create financial settings.
                  </p>
                )}
              </div>
            ) : (
              <>
                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                  <div className="rounded-xl border border-slate-200 p-5">
                    <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                      Currency
                    </p>

                    <p className="mt-2 text-lg font-bold text-slate-950">
                      {
                        financialSetting.currency_code
                      }
                    </p>
                  </div>

                  <div className="rounded-xl border border-slate-200 p-5">
                    <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                      Payment method
                    </p>

                    <p className="mt-2 text-lg font-bold text-slate-950">
                      {formatLabel(
                        financialSetting.default_payment_method
                      )}
                    </p>
                  </div>

                  <div className="rounded-xl border border-slate-200 p-5">
                    <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                      Payment term
                    </p>

                    <p className="mt-2 text-lg font-bold text-slate-950">
                      {
                        financialSetting.payment_term_days
                      }{" "}
                      days
                    </p>
                  </div>

                  <div className="rounded-xl border border-slate-200 p-5">
                    <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                      Credit limit
                    </p>

                    <p className="mt-2 text-lg font-bold text-slate-950">
                      {formatMoney(
                        financialSetting.credit_limit,
                        financialSetting.currency_code
                      )}
                    </p>

                    <p className="mt-2 text-xs text-slate-500">
                      {financialSetting.allow_credit_sale
                        ? "Credit sales enabled"
                        : "Credit sales disabled"}
                    </p>
                  </div>

                  <div className="rounded-xl border border-slate-200 p-5">
                    <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                      Default sales discount
                    </p>

                    <p className="mt-2 text-lg font-bold text-slate-950">
                      {
                        financialSetting.default_sales_discount_percent
                      }
                      %
                    </p>
                  </div>

                  <div className="rounded-xl border border-slate-200 p-5">
                    <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                      Status
                    </p>

                    <span
                      className={`mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${
                        financialSetting.is_active
                          ? "bg-emerald-100 text-emerald-800"
                          : "bg-red-100 text-red-800"
                      }`}
                    >
                      {financialSetting.is_active
                        ? "Active"
                        : "Inactive"}
                    </span>
                  </div>
                </section>

                <section className="rounded-xl border border-slate-200">
                  <div className="border-b border-slate-200 px-5 py-4">
                    <h3 className="font-bold text-slate-900">
                      Sales Configuration
                    </h3>
                  </div>

                  <dl className="grid gap-5 p-5 md:grid-cols-2">
                    <div>
                      <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Sales price basis
                      </dt>

                      <dd className="mt-1 text-sm font-semibold text-slate-800">
                        {formatLabel(
                          financialSetting.sales_price_basis
                        )}
                      </dd>
                    </div>

                    <div>
                      <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Sales order term
                      </dt>

                      <dd className="mt-1 text-sm font-semibold text-slate-800">
                        {formatLabel(
                          financialSetting.default_sales_order_term
                        )}
                      </dd>
                    </div>

                    <div>
                      <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Block over credit limit
                      </dt>

                      <dd className="mt-1 text-sm font-semibold text-slate-800">
                        {financialSetting.block_sale_on_credit_limit
                          ? "Yes"
                          : "No"}
                      </dd>
                    </div>

                    <div>
                      <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Tax applicable
                      </dt>

                      <dd className="mt-1 text-sm font-semibold text-slate-800">
                        {financialSetting.is_tax_applicable
                          ? `${financialSetting.default_tax_percent}%`
                          : "No"}
                      </dd>
                    </div>

                    <div>
                      <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Withholding tax
                      </dt>

                      <dd className="mt-1 text-sm font-semibold text-slate-800">
                        {financialSetting.is_withholding_tax_applicable
                          ? `${financialSetting.withholding_tax_percent}%`
                          : "No"}
                      </dd>
                    </div>
                  </dl>
                </section>

                <section className="grid gap-5 md:grid-cols-2">
                  <div className="rounded-xl border border-slate-200 p-5">
                    <h3 className="font-bold text-slate-900">
                      Payment Instructions
                    </h3>

                    <p className="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-600">
                      {financialSetting.payment_instruction ??
                        "No payment instructions provided."}
                    </p>
                  </div>

                  <div className="rounded-xl border border-slate-200 p-5">
                    <h3 className="font-bold text-slate-900">
                      Internal Notes
                    </h3>

                    <p className="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-600">
                      {financialSetting.notes ??
                        "No internal notes provided."}
                    </p>
                  </div>
                </section>

                {(canUpdate || canDelete) && (
                  <div className="flex flex-col justify-end gap-3 border-t border-slate-200 pt-5 sm:flex-row">
                    {canDelete && (
                      <button
                        type="button"
                        onClick={() =>
                          void handleDelete()
                        }
                        disabled={isBusy}
                        className="rounded-lg border border-red-300 px-5 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                      >
                        {isDeleting
                          ? "Deleting..."
                          : "Delete settings"}
                      </button>
                    )}

                    {canUpdate && (
                      <button
                        type="button"
                        onClick={openEditForm}
                        disabled={isBusy}
                        className="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-emerald-400"
                      >
                        Edit financial settings
                      </button>
                    )}
                  </div>
                )}
              </>
            )}
          </div>
        </div>
      </div>

      {isFormOpen && (
        <CustomerFinancialSettingForm
          key={
            financialSetting?.id ??
            `new-financial-setting-${customer.id}`
          }
          customer={customer}
          financialSetting={
            financialSetting
          }
          isSaving={isSaving}
          errors={formErrors}
          onCancel={closeForm}
          onSubmit={handleSave}
        />
      )}
    </>
  );
}