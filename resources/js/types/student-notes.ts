export type StudentNoteGroup = {
    id: number;
    name: string;
    notesCount: number;
};

export type StudentNote = {
    id: number;
    studentNoteGroupId: number;
    title: string;
    body: string;
    bodyPreview: string;
    createdAt: string | null;
    updatedAt: string | null;
};

export type DashboardRecentNote = {
    id: number;
    studentNoteGroupId: number;
    groupName: string;
    title: string;
    bodyPreview: string;
    updatedAt: string | null;
};
