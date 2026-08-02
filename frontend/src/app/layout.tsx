import type { Metadata } from "next";
import {
  Geist,
  Geist_Mono,
} from "next/font/google";

import { AuthProvider } from "@/contexts/AuthContext";

import "./globals.css";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: {
    default:
      "Desh Solar Inventory Management System",
    template: "%s | Desh Solar",
  },

  description:
    "Secure inventory, warehouse, supplier, purchasing, sales, and operational management system for Desh Solar.",

  applicationName:
    "Desh Solar Inventory Management System",

  robots: {
    index: false,
    follow: false,
  },
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html
      lang="en"
      className={`${geistSans.variable} ${geistMono.variable} h-full antialiased`}
    >
      <body
        suppressHydrationWarning
        className="flex min-h-full flex-col"
      >
        <AuthProvider>
          {children}
        </AuthProvider>
      </body>
    </html>
  );
}