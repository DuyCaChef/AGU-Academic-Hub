import { Head } from "@inertiajs/react";
import AppLayout from "../../Layouts/AppLayout";

export default function Index({ documents }) {
    return (
        <AppLayout>
            <Head title="Kho tài liệu" />
            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <h1 className="text-2xl font-bold text-text-primary">
                    Kho tài liệu
                </h1>
                <p className="mt-2 text-text-secondary">
                    {documents.meta?.total ?? documents.data.length} tài liệu.
                </p>
            </section>
        </AppLayout>
    );
}
