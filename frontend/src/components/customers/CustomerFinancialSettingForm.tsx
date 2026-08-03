"use client";

import {
  type ChangeEvent,
  type FormEvent,
  useState,
} from "react";

import type {
  Customer,
  CustomerFinancialSetting,
  CustomerFinancialSettingPayload,
  CustomerPaymentMethod,
  CustomerSalesOrderTerm,
  CustomerSalesPriceBasis,
} from "@/types/customer";

interface CustomerFinancialSettingFormProps {
  customer: Customer;
  financialSetting:
    | CustomerFinancialSetting
    | null;
  isSaving: boolean;
  errors: Record<string, string>;
  onCancel: () => void;
  onSubmit: (
    payload: CustomerFinancialSettingPayload
  ) => Promise<void>;
}

interface FinancialSettingFormState {
  currency_code: string;

  default_payment_method:
    CustomerPaymentMethod;

  payment_term_days: string;
  credit_limit: string;

  allow_credit_sale: boolean;
  block_sale_on_credit_limit: boolean;

  default_sales_discount_percent:
    string;

  is_tax_applicable: boolean;
  default_tax_percent: string;

  is_withholding_tax_applicable:
    boolean;

  withholding_tax_percent: string;

  sales_price_basis:
    CustomerSalesPriceBasis;

  default_sales_order_term:
    CustomerSalesOrderTerm;

  payment_instruction: string;
  notes: string;

  is_active: boolean;
}

function createInitialState(
  customer: Customer,
  financialSetting:
    | CustomerFinancialSetting
    | null
): FinancialSettingFormState {
  if (financialSetting) {
    return {
      currency_code:
        financialSetting.currency_code,

      default_payment_method:
        financialSetting.default_payment_method,

      payment_term_days: String(
        financialSetting.payment_term_days
      ),

      credit_limit:
        financialSetting.credit_limit,

      allow_credit_sale:
        financialSetting.allow_credit_sale,

      block_sale_on_credit_limit:
        financialSetting.block_sale_on_credit_limit,

      default_sales_discount_percent:
        financialSetting
          .default_sales_discount_percent,

      is_tax_applicable:
        financialSetting.is_tax_applicable,

      default_tax_percent:
        financialSetting.default_tax_percent,

      is_withholding_tax_applicable:
        financialSetting
          .is_withholding_tax_applicable,

      withholding_tax_percent:
        financialSetting
          .withholding_tax_percent,

      sales_price_basis:
        financialSetting.sales_price_basis,

      default_sales_order_term:
        financialSetting
          .default_sales_order_term,

      payment_instruction:
        financialSetting
          .payment_instruction ?? "",

      notes:
        financialSetting.notes ?? "",

      is_active:
        financialSetting.is_active,
    };
  }

  const hasCreditLimit =
    Number(customer.credit_limit) > 0;

  return {
    currency_code: "BDT",

    default_payment_method:
      hasCreditLimit
        ? "credit"
        : "cash",

    payment_term_days: String(
      customer.payment_term_days ?? 0
    ),

    credit_limit:
      customer.credit_limit ?? "0",

    allow_credit_sale:
      hasCreditLimit,

    block_sale_on_credit_limit: true,

    default_sales_discount_percent:
      "0",

    is_tax_applicable: false,
    default_tax_percent: "0",

    is_withholding_tax_applicable:
      false,

    withholding_tax_percent: "0",

    sales_price_basis:
      "exclusive_of_tax",

    default_sales_order_term:
      hasCreditLimit
        ? "credit"
        : "standard",

    payment_instruction: "",
    notes: "",

    is_active: true,
  };
}

function toNumber(
  value: string
): number {
  const parsedValue = Number(value);

  return Number.isFinite(parsedValue)
    ? parsedValue
    : 0;
}

export function CustomerFinancialSettingForm({
  customer,
  financialSetting,
  isSaving,
  errors,
  onCancel,
  onSubmit,
}: CustomerFinancialSettingFormProps) {
  const [form, setForm] =
    useState<FinancialSettingFormState>(
      createInitialState(
        customer,
        financialSetting
      )
    );

  function handleTextChange(
    event: ChangeEvent<
      HTMLInputElement |
        HTMLSelectElement |
        HTMLTextAreaElement
    >
  ): void {
    const { name, value } =
      event.target;

    setForm((current) => ({
      ...current,
      [name]: value,
    }));
  }

  function handleCheckboxChange(
    event: ChangeEvent<HTMLInputElement>
  ): void {
    const { name, checked } =
      event.target;

    setForm((current) => {
      const nextState = {
        ...current,
        [name]: checked,
      };

      if (
        name ===
          "allow_credit_sale" &&
        !checked
      ) {
        nextState.credit_limit = "0";

        if (
          nextState.default_payment_method ===
          "credit"
        ) {
          nextState.default_payment_method =
            "cash";
        }

        if (
          nextState.default_sales_order_term ===
          "credit"
        ) {
          nextState.default_sales_order_term =
            "standard";
        }
      }

      if (
        name ===
          "is_tax_applicable" &&
        !checked
      ) {
        nextState.default_tax_percent =
          "0";
      }

      if (
        name ===
          "is_withholding_tax_applicable" &&
        !checked
      ) {
        nextState.withholding_tax_percent =
          "0";
      }

      return nextState;
    });
  }

  function handleSubmit(
    event: FormEvent<HTMLFormElement>
  ): void {
    event.preventDefault();

    const payload: CustomerFinancialSettingPayload =
      {
        customer_id: customer.id,

        currency_code:
          form.currency_code
            .trim()
            .toUpperCase(),

        default_payment_method:
          form.default_payment_method,

        payment_term_days: Math.max(
          0,
          Math.trunc(
            toNumber(
              form.payment_term_days
            )
          )
        ),

        credit_limit:
          form.allow_credit_sale
            ? Math.max(
                0,
                toNumber(
                  form.credit_limit
                )
              )
            : 0,

        allow_credit_sale:
          form.allow_credit_sale,

        block_sale_on_credit_limit:
          form.allow_credit_sale
            ? form.block_sale_on_credit_limit
            : false,

        default_sales_discount_percent:
          Math.min(
            100,
            Math.max(
              0,
              toNumber(
                form.default_sales_discount_percent
              )
            )
          ),

        is_tax_applicable:
          form.is_tax_applicable,

        default_tax_percent:
          form.is_tax_applicable
            ? Math.min(
                100,
                Math.max(
                  0,
                  toNumber(
                    form.default_tax_percent
                  )
                )
              )
            : 0,

        is_withholding_tax_applicable:
          form.is_withholding_tax_applicable,

        withholding_tax_percent:
          form
            .is_withholding_tax_applicable
            ? Math.min(
                100,
                Math.max(
                  0,
                  toNumber(
                    form.withholding_tax_percent
                  )
                )
              )
            : 0,

        sales_price_basis:
          form.sales_price_basis,

        default_sales_order_term:
          form.default_sales_order_term,

        payment_instruction:
          form.payment_instruction.trim() ||
          null,

        notes:
          form.notes.trim() || null,

        is_active:
          form.is_active,
      };

    void onSubmit(payload);
  }

  function fieldError(
    field: string
  ): string | undefined {
    return errors[field];
  }

  const inputClassName =
    "h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:cursor-not-allowed disabled:bg-slate-100";

  const textareaClassName =
    "w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:cursor-not-allowed disabled:bg-slate-100";

  return (
    <div className="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/60 p-4">
      <div className="max-h-[95vh] w-full max-w-6xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div className="flex flex-col justify-between gap-4 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center">
          <div>
            <h2 className="text-xl font-bold text-slate-950">
              {financialSetting
                ? "Edit Financial Settings"
                : "Create Financial Settings"}
            </h2>

            <p className="mt-1 text-sm text-slate-500">
              {customer.name} ·{" "}
              {customer.code}
            </p>
          </div>

          <button
            type="button"
            onClick={onCancel}
            disabled={isSaving}
            className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
          >
            Close
          </button>
        </div>

        <form
          onSubmit={handleSubmit}
          className="space-y-7 p-6"
        >
          <section className="rounded-xl border border-slate-200 p-5">
            <div className="mb-5">
              <h3 className="text-base font-bold text-slate-900">
                Payment Configuration
              </h3>

              <p className="mt-1 text-sm text-slate-500">
                Define the customer currency,
                payment method, payment terms,
                pricing, discount, and sales-order
                defaults.
              </p>
            </div>

            <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
              <div>
                <label
                  htmlFor="currency_code"
                  className="mb-2 block text-sm font-semibold text-slate-700"
                >
                  Currency code
                </label>

                <input
                  id="currency_code"
                  name="currency_code"
                  value={form.currency_code}
                  onChange={handleTextChange}
                  maxLength={3}
                  required
                  disabled={isSaving}
                  placeholder="BDT"
                  className={`${inputClassName} uppercase`}
                />

                {fieldError(
                  "currency_code"
                ) && (
                  <p className="mt-1 text-xs text-red-600">
                    {fieldError(
                      "currency_code"
                    )}
                  </p>
                )}
              </div>

              <div>
                <label
                  htmlFor="default_payment_method"
                  className="mb-2 block text-sm font-semibold text-slate-700"
                >
                  Default payment method
                </label>

                <select
                  id="default_payment_method"
                  name="default_payment_method"
                  value={
                    form.default_payment_method
                  }
                  onChange={handleTextChange}
                  disabled={isSaving}
                  className={inputClassName}
                >
                  <option value="cash">
                    Cash
                  </option>

                  <option value="bank_transfer">
                    Bank transfer
                  </option>

                  <option value="mobile_banking">
                    Mobile banking
                  </option>

                  <option value="cheque">
                    Cheque
                  </option>

                  <option value="card">
                    Card
                  </option>

                  <option
                    value="credit"
                    disabled={
                      !form.allow_credit_sale
                    }
                  >
                    Credit
                  </option>
                </select>

                {fieldError(
                  "default_payment_method"
                ) && (
                  <p className="mt-1 text-xs text-red-600">
                    {fieldError(
                      "default_payment_method"
                    )}
                  </p>
                )}
              </div>

              <div>
                <label
                  htmlFor="payment_term_days"
                  className="mb-2 block text-sm font-semibold text-slate-700"
                >
                  Payment term days
                </label>

                <input
                  id="payment_term_days"
                  name="payment_term_days"
                  type="number"
                  min={0}
                  max={3650}
                  step={1}
                  value={
                    form.payment_term_days
                  }
                  onChange={handleTextChange}
                  disabled={isSaving}
                  className={inputClassName}
                />

                {fieldError(
                  "payment_term_days"
                ) && (
                  <p className="mt-1 text-xs text-red-600">
                    {fieldError(
                      "payment_term_days"
                    )}
                  </p>
                )}
              </div>

              <div>
                <label
                  htmlFor="sales_price_basis"
                  className="mb-2 block text-sm font-semibold text-slate-700"
                >
                  Sales price basis
                </label>

                <select
                  id="sales_price_basis"
                  name="sales_price_basis"
                  value={
                    form.sales_price_basis
                  }
                  onChange={handleTextChange}
                  disabled={isSaving}
                  className={inputClassName}
                >
                  <option value="exclusive_of_tax">
                    Exclusive of tax
                  </option>

                  <option value="inclusive_of_tax">
                    Inclusive of tax
                  </option>
                </select>

                {fieldError(
                  "sales_price_basis"
                ) && (
                  <p className="mt-1 text-xs text-red-600">
                    {fieldError(
                      "sales_price_basis"
                    )}
                  </p>
                )}
              </div>

              <div>
                <label
                  htmlFor="default_sales_order_term"
                  className="mb-2 block text-sm font-semibold text-slate-700"
                >
                  Sales order term
                </label>

                <select
                  id="default_sales_order_term"
                  name="default_sales_order_term"
                  value={
                    form.default_sales_order_term
                  }
                  onChange={handleTextChange}
                  disabled={isSaving}
                  className={inputClassName}
                >
                  <option value="standard">
                    Standard
                  </option>

                  <option value="advance_payment">
                    Advance payment
                  </option>

                  <option value="cash_on_delivery">
                    Cash on delivery
                  </option>

                  <option value="partial_advance">
                    Partial advance
                  </option>

                  <option
                    value="credit"
                    disabled={
                      !form.allow_credit_sale
                    }
                  >
                    Credit
                  </option>
                </select>

                {fieldError(
                  "default_sales_order_term"
                ) && (
                  <p className="mt-1 text-xs text-red-600">
                    {fieldError(
                      "default_sales_order_term"
                    )}
                  </p>
                )}
              </div>

              <div>
                <label
                  htmlFor="default_sales_discount_percent"
                  className="mb-2 block text-sm font-semibold text-slate-700"
                >
                  Default sales discount %
                </label>

                <input
                  id="default_sales_discount_percent"
                  name="default_sales_discount_percent"
                  type="number"
                  min={0}
                  max={100}
                  step="0.01"
                  value={
                    form.default_sales_discount_percent
                  }
                  onChange={handleTextChange}
                  disabled={isSaving}
                  className={inputClassName}
                />

                {fieldError(
                  "default_sales_discount_percent"
                ) && (
                  <p className="mt-1 text-xs text-red-600">
                    {fieldError(
                      "default_sales_discount_percent"
                    )}
                  </p>
                )}
              </div>
            </div>
          </section>

          <section className="rounded-xl border border-slate-200 p-5">
            <div className="mb-5">
              <h3 className="text-base font-bold text-slate-900">
                Credit Configuration
              </h3>

              <p className="mt-1 text-sm text-slate-500">
                Control customer credit sales
                and credit-limit enforcement.
              </p>
            </div>

            <div className="grid gap-5 md:grid-cols-2">
              <label className="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4">
                <input
                  type="checkbox"
                  name="allow_credit_sale"
                  checked={
                    form.allow_credit_sale
                  }
                  onChange={
                    handleCheckboxChange
                  }
                  disabled={isSaving}
                  className="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-600"
                />

                <span>
                  <span className="block text-sm font-semibold text-slate-800">
                    Allow credit sales
                  </span>

                  <span className="mt-1 block text-xs text-slate-500">
                    Allow this customer to
                    purchase products using an
                    approved credit limit.
                  </span>
                </span>
              </label>

              <label className="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4">
                <input
                  type="checkbox"
                  name="block_sale_on_credit_limit"
                  checked={
                    form.block_sale_on_credit_limit
                  }
                  onChange={
                    handleCheckboxChange
                  }
                  disabled={
                    isSaving ||
                    !form.allow_credit_sale
                  }
                  className="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-600"
                />

                <span>
                  <span className="block text-sm font-semibold text-slate-800">
                    Block over-limit sales
                  </span>

                  <span className="mt-1 block text-xs text-slate-500">
                    Prevent new sales when the
                    customer&apos;s approved credit
                    limit has been exceeded.
                  </span>
                </span>
              </label>

              <div className="md:col-span-2">
                <label
                  htmlFor="credit_limit"
                  className="mb-2 block text-sm font-semibold text-slate-700"
                >
                  Credit limit
                </label>

                <input
                  id="credit_limit"
                  name="credit_limit"
                  type="number"
                  min={0}
                  step="0.01"
                  value={form.credit_limit}
                  onChange={handleTextChange}
                  disabled={
                    isSaving ||
                    !form.allow_credit_sale
                  }
                  className={inputClassName}
                />

                {fieldError(
                  "credit_limit"
                ) && (
                  <p className="mt-1 text-xs text-red-600">
                    {fieldError(
                      "credit_limit"
                    )}
                  </p>
                )}
              </div>
            </div>
          </section>

          <section className="rounded-xl border border-slate-200 p-5">
            <div className="mb-5">
              <h3 className="text-base font-bold text-slate-900">
                Tax Configuration
              </h3>

              <p className="mt-1 text-sm text-slate-500">
                Configure sales tax and
                withholding tax defaults.
              </p>
            </div>

            <div className="grid gap-5 md:grid-cols-2">
              <div className="space-y-4">
                <label className="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4">
                  <input
                    type="checkbox"
                    name="is_tax_applicable"
                    checked={
                      form.is_tax_applicable
                    }
                    onChange={
                      handleCheckboxChange
                    }
                    disabled={isSaving}
                    className="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-600"
                  />

                  <span>
                    <span className="block text-sm font-semibold text-slate-800">
                      Sales tax applicable
                    </span>

                    <span className="mt-1 block text-xs text-slate-500">
                      Apply the default tax rate
                      to this customer&apos;s sales.
                    </span>
                  </span>
                </label>

                <div>
                  <label
                    htmlFor="default_tax_percent"
                    className="mb-2 block text-sm font-semibold text-slate-700"
                  >
                    Default tax %
                  </label>

                  <input
                    id="default_tax_percent"
                    name="default_tax_percent"
                    type="number"
                    min={0}
                    max={100}
                    step="0.01"
                    value={
                      form.default_tax_percent
                    }
                    onChange={handleTextChange}
                    disabled={
                      isSaving ||
                      !form.is_tax_applicable
                    }
                    className={inputClassName}
                  />

                  {fieldError(
                    "default_tax_percent"
                  ) && (
                    <p className="mt-1 text-xs text-red-600">
                      {fieldError(
                        "default_tax_percent"
                      )}
                    </p>
                  )}
                </div>
              </div>

              <div className="space-y-4">
                <label className="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4">
                  <input
                    type="checkbox"
                    name="is_withholding_tax_applicable"
                    checked={
                      form
                        .is_withholding_tax_applicable
                    }
                    onChange={
                      handleCheckboxChange
                    }
                    disabled={isSaving}
                    className="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-600"
                  />

                  <span>
                    <span className="block text-sm font-semibold text-slate-800">
                      Withholding tax applicable
                    </span>

                    <span className="mt-1 block text-xs text-slate-500">
                      Apply withholding tax to
                      relevant customer payment
                      transactions.
                    </span>
                  </span>
                </label>

                <div>
                  <label
                    htmlFor="withholding_tax_percent"
                    className="mb-2 block text-sm font-semibold text-slate-700"
                  >
                    Withholding tax %
                  </label>

                  <input
                    id="withholding_tax_percent"
                    name="withholding_tax_percent"
                    type="number"
                    min={0}
                    max={100}
                    step="0.01"
                    value={
                      form.withholding_tax_percent
                    }
                    onChange={handleTextChange}
                    disabled={
                      isSaving ||
                      !form
                        .is_withholding_tax_applicable
                    }
                    className={inputClassName}
                  />

                  {fieldError(
                    "withholding_tax_percent"
                  ) && (
                    <p className="mt-1 text-xs text-red-600">
                      {fieldError(
                        "withholding_tax_percent"
                      )}
                    </p>
                  )}
                </div>
              </div>
            </div>
          </section>

          <section className="rounded-xl border border-slate-200 p-5">
            <div className="mb-5">
              <h3 className="text-base font-bold text-slate-900">
                Instructions and Status
              </h3>
            </div>

            <div className="grid gap-5 md:grid-cols-2">
              <div>
                <label
                  htmlFor="payment_instruction"
                  className="mb-2 block text-sm font-semibold text-slate-700"
                >
                  Payment instructions
                </label>

                <textarea
                  id="payment_instruction"
                  name="payment_instruction"
                  rows={4}
                  value={
                    form.payment_instruction
                  }
                  onChange={handleTextChange}
                  disabled={isSaving}
                  placeholder="Bank details, payment references, cheque instructions, or collection conditions..."
                  className={textareaClassName}
                />

                {fieldError(
                  "payment_instruction"
                ) && (
                  <p className="mt-1 text-xs text-red-600">
                    {fieldError(
                      "payment_instruction"
                    )}
                  </p>
                )}
              </div>

              <div>
                <label
                  htmlFor="notes"
                  className="mb-2 block text-sm font-semibold text-slate-700"
                >
                  Internal notes
                </label>

                <textarea
                  id="notes"
                  name="notes"
                  rows={4}
                  value={form.notes}
                  onChange={handleTextChange}
                  disabled={isSaving}
                  placeholder="Additional internal financial notes..."
                  className={textareaClassName}
                />

                {fieldError("notes") && (
                  <p className="mt-1 text-xs text-red-600">
                    {fieldError("notes")}
                  </p>
                )}
              </div>
            </div>

            <label className="mt-5 flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4">
              <input
                type="checkbox"
                name="is_active"
                checked={form.is_active}
                onChange={handleCheckboxChange}
                disabled={isSaving}
                className="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-600"
              />

              <span>
                <span className="block text-sm font-semibold text-slate-800">
                  Financial settings active
                </span>

                <span className="mt-1 block text-xs text-slate-500">
                  Active settings may be used as
                  defaults in future sales,
                  invoicing, and payment
                  workflows.
                </span>
              </span>
            </label>
          </section>

          {fieldError("customer_id") && (
            <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {fieldError("customer_id")}
            </div>
          )}

          <div className="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
            <button
              type="button"
              onClick={onCancel}
              disabled={isSaving}
              className="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
              Cancel
            </button>

            <button
              type="submit"
              disabled={
                isSaving ||
                form.currency_code
                  .trim()
                  .length !== 3 ||
                (
                  form.allow_credit_sale &&
                  toNumber(
                    form.credit_limit
                  ) <= 0
                )
              }
              className="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-emerald-400"
            >
              {isSaving
                ? "Saving..."
                : financialSetting
                  ? "Update financial settings"
                  : "Create financial settings"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}