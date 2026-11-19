import React, { useState } from "react";
import { Link } from "@inertiajs/react";
import { ChevronDown, Folder, FolderOpen, BookOpen, Layers } from "lucide-react";

export default function FacultyCourseTree({ faculties = [], faculities = [], onItemClick }) {
    // Hỗ trợ cả 2 tên prop để đảm bảo tương thích ngược
    const facultyList = faculties?.length > 0 ? faculties : (faculities || []);

    const [expandedFaculty, setExpandedFaculty] = useState(null);

    const toggleFaculty = (makhoa) => {
        setExpandedFaculty((current) => (current === makhoa ? null : makhoa));
    };

    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between mb-1.5 px-1">
                <p className="text-xs font-semibold text-slate-500">
                    Khoa / Bộ môn
                </p>
                <span className="font-mono text-[11px] font-medium text-slate-400 tabular-nums">
                    {facultyList.length} Khoa
                </span>
            </div>

            {facultyList.length === 0 ? (
                <p className="text-xs text-slate-400 italic py-2 px-1">
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
                                className={`flex w-full items-center justify-between rounded-lg px-2.5 py-2 text-left text-xs font-medium transition-colors cursor-pointer ${
                                    isExpanded
                                        ? "bg-blue-50/80 text-[#005BAA] font-semibold"
                                        : "text-slate-700 hover:bg-slate-50 hover:text-slate-900"
                                }`}
                            >
                                <span className="flex items-center gap-2.5 min-w-0 flex-1 pr-1">
                                    {isExpanded ? (
                                        <FolderOpen
                                            size={18}
                                            strokeWidth={1.75}
                                            className="shrink-0 text-[#005BAA] fill-blue-100"
                                        />
                                    ) : (
                                        <Folder
                                            size={18}
                                            strokeWidth={1.75}
                                            className="shrink-0 text-amber-500 fill-amber-100/70"
                                        />
                                    )}
                                    <span className="truncate">{khoa.ten_khoa}</span>
                                </span>
                                <ChevronDown
                                    size={14}
                                    className={`shrink-0 text-slate-400 transition-transform duration-200 ${
                                        isExpanded ? "rotate-180 text-[#005BAA]" : ""
                                    }`}
                                />
                            </button>

                            {isExpanded && (
                                <div className="ml-4 mt-1 pl-2.5 border-l-2 border-slate-100 space-y-1 py-1">
                                    {/* Chuyên ngành badge (nếu có) */}
                                    {majors.length > 0 && (
                                        <div className="flex items-center gap-1.5 px-2 py-0.5 text-[10px] text-slate-500 font-medium">
                                            <Layers size={11} className="text-amber-600 shrink-0" />
                                            <span>{majors.length} chuyên ngành</span>
                                        </div>
                                    )}

                                    {courses.length > 0 ? (
                                        courses.map((mon) => (
                                            <Link
                                                key={mon.ma_mon_hoc}
                                                href={route("courses.show", mon.ma_hoc_phan)}
                                                onClick={onItemClick}
                                                className="block rounded-md px-2 py-1.5 text-xs text-slate-600 hover:text-[#005BAA] hover:bg-blue-50/60 transition-colors"
                                            >
                                                <div className="font-medium text-slate-800 hover:text-[#005BAA] truncate">
                                                    {mon.ten_mon_hoc}
                                                </div>
                                                <div className="text-[10px] font-mono text-slate-400">
                                                    {mon.ma_hoc_phan}
                                                </div>
                                            </Link>
                                        ))
                                    ) : (
                                        <p className="px-2 py-1 text-[11px] text-slate-400 italic">
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
