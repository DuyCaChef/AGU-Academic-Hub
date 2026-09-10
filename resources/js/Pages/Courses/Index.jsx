import { Head } from "@inertiajs/react";
import AppLayout from "../../Layouts/AppLayout";

export default function Index({ courses }) {
    return (
        <AppLayout>
            <Head title="Danh mục môn học" />
            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <h1 className="text-2xl font-bold text-text-primary">
                    Danh mục môn học
                </h1>
                <p className="mt-2 text-text-secondary">
                    {courses.meta?.total ?? courses.data.length} môn học.
                </p>
            </section>
        </AppLayout>
    );
}
