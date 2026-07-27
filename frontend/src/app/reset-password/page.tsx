import { ResetPasswordForm } from "@/components/auth/ResetPasswordForm";

type SearchParamValue =
  | string
  | string[]
  | undefined;

interface ResetPasswordPageProps {
  searchParams: Promise<{
    token?: SearchParamValue;
    email?: SearchParamValue;
  }>;
}

function firstValue(
  value: SearchParamValue
): string {
  if (Array.isArray(value)) {
    return value[0] ?? "";
  }

  return value ?? "";
}

export default async function ResetPasswordPage({
  searchParams,
}: ResetPasswordPageProps) {
  const params = await searchParams;

  return (
    <ResetPasswordForm
      initialToken={firstValue(params.token)}
      initialEmail={firstValue(params.email)}
    />
  );
}