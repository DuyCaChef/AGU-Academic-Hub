import { Head } from "@inertiajs/react";
import AppLayout from "../Layouts/AppLayout";

export default function Home() {
    return (
        <AppLayout>
            <Head title="Trang chủ" />
            <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <p className="mb-3 font-mono text-sm text-brand-secondary">
                    AGU ACADEMIC HUB
                </p>
                <h1 className="max-w-3xl text-3xl font-bold tracking-tight text-text-primary sm:text-4xl">
                    Nền tảng tri thức và cộng đồng sinh viên nội bộ.
                </h1>
                <p className="mt-4 max-w-2xl text-base leading-relaxed text-text-secondary">
                    Laravel 11, Inertia.js, React và Tailwind CSS được tổ chức
                    theo mô hình Monolithic Modular.
                </p>
            </section>
        </AppLayout>
    );
}
