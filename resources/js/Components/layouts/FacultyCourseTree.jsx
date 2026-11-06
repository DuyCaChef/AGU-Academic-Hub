import React, { useState } from "react";
import { Link } from "@inertiajs/react";
import { ChevronDown, FolderKanban, BookOpen, Layers } from "lucide-react";

export default function FacultyCourseTree({ faculties = [], faculities = [] }) {
    // Hỗ trợ cả 2 tên prop để đảm bảo tương thích ngược
    const facultyList = faculties?.length > 0 ? faculties : (faculities || []);

    const [expandedFaculty, setExpandedFaculty] = useState(null);

    const toggleFaculty = (makhoa) => {
        setExpandedFaculty((current) => (current === makhoa ? null : makhoa));
    };

    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between mb-1">
                <p className="text-xs font-semibold uppercase tracking-wider text-text-secondary">
                    Khoa / Bộ môn
                </p>
                <span className="text-xs text-text-muted">
                    {facultyList.length} Khoa
                </span>
            </div>

            {facultyList.length === 0 ? (
                <p className="text-xs text-text-muted italic py-2">
                    Chưa có dữ liệu khoa...
                </p>
            ) : (
                facultyList.map((khoa) => {
                    const courses = khoa.mon_hocs || khoa.monHocs || [];
                    const majors = khoa.chuyen_nganhs || khoa.chuyenNganhs || [];
                    const isExpanded = expandedFaculty === khoa.ma_khoa;

                    return (
                        <div key={khoa.ma_khoa} className="rounded-lg overflow-hidden">
                            <button
                                type="button"
                                onClick={() => toggleFaculty(khoa.ma_khoa)}
                                className={`flex w-full items-center justify-between rounded-lg p-2 text-left text-xs font-medium transition-colors ${
                                    isExpanded
                                        ? "bg-brand-primary/10 text-brand-primary font-semibold"
                                        : "hover:bg-surface-page text-text-primary"
                                }`}
                            >
                                <span className="flex items-center gap-2 truncate pr-1">
                                    <FolderKanban size={16} className={isExpanded ? "text-brand-primary" : "text-text-secondary"} />
                                    <span className="truncate">{khoa.ten_khoa}</span>
                                </span>
                                <ChevronDown
                                    size={14}
                                    className={`shrink-0 text-text-secondary transition-transform duration-200 ${
                                        isExpanded ? "rotate-180 text-brand-primary" : ""
                                    }`}
                                />
                            </button>

                            {isExpanded && (
                                <div className="ml-3 mt-1 pl-2 border-l border-border-default space-y-1 py-1">
                                    {/* Chuyên ngành badge (nếu có) */}
                                    {majors.length > 0 && (
                                        <div className="flex items-center gap-1 px-2 py-1 text-[10px] text-text-muted font-medium">
                                            <Layers size={11} className="text-amber-600" />
                                            <span>{majors.length} chuyên ngành</span>
                                        </div>
                                    )}

                                    {courses.length > 0 ? (
                                        courses.map((mon) => (
                                            <Link
                                                key={mon.ma_mon_hoc}
                                                href={route("courses.show", mon.ma_hoc_phan)}
                                                className="block rounded-md p-1.5 text-xs text-text-secondary hover:text-brand-primary hover:bg-surface-page transition-colors"
                                            >
                                                <div className="font-medium text-text-primary truncate">
                                                    {mon.ten_mon_hoc}
                                                </div>
                                                <div className="text-[10px] font-mono text-text-muted">
                                                    {mon.ma_hoc_phan}
                                                </div>
                                            </Link>
                                        ))
                                    ) : (
                                        <p className="p-2 text-[11px] text-text-muted italic">
                                            Chưa có môn học
                                        </p>
                                    )}
                                </div>
                            )}
                        </div>
                    );
                })
            )}
        </div>
    );
}
