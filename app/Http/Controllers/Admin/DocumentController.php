<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Main\Tenant;
use App\Models\Tenant\Document;
use App\Models\Tenant\DocumentFolder;
use App\Models\Tenant\DocumentSignature;
use App\Models\Tenant\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /* ================================================================
     | INDEX
     |================================================================*/
    public function index(string $tenant, Request $request)
    {
        $folderId = $request->get('folder');
        $category = $request->get('category');
        $search   = $request->get('search');

        // Folders
        $folders = DocumentFolder::withCount('documents')
            ->where('parent_id', $folderId ?: null)
            ->orderBy('name')
            ->get();

        // Documents
        $query = Document::with(['folder', 'uploader', 'signatures'])
            ->whereNull('parent_document_id') // only latest versions
            ->latest();

        if ($folderId) $query->where('folder_id', $folderId);
        else if (! $search && ! $category) $query->whereNull('folder_id');

        if ($category) $query->where('category', $category);
        if ($search)   $query->where(fn($q) => $q
            ->where('title', 'like', "%$search%")
            ->orWhere('file_name', 'like', "%$search%")
        );

        $documents = $query->paginate(20)->withQueryString();

        // Breadcrumb
        $breadcrumbs = [];
        if ($folderId) {
            $folder = DocumentFolder::find($folderId);
            $breadcrumbs[] = $folder;
            while ($folder?->parent_id) {
                $folder = $folder->parent;
                array_unshift($breadcrumbs, $folder);
            }
        }

        $currentFolder = $folderId ? DocumentFolder::find($folderId) : null;
        $allFolders    = DocumentFolder::orderBy('name')->get();
        $employees     = Employee::active()->orderBy('first_name')->get();

        $stats = [
            'total'     => Document::count(),
            'policies'  => Document::where('category', 'policy')->count(),
            'contracts' => Document::where('category', 'contract')->count(),
            'expiring'  => Document::whereNotNull('expiry_date')
                            ->where('expiry_date', '>', now())
                            ->where('expiry_date', '<=', now()->addDays(30))
                            ->count(),
            'pending_sign' => Document::where('requires_signature', true)
                                ->whereDoesntHave('signatures')->count(),
        ];

        return view('admin.documents.index', compact(
            'folders', 'documents', 'breadcrumbs',
            'currentFolder', 'allFolders', 'employees',
            'stats', 'folderId', 'tenant'
        ));
    }

    /* ================================================================
     | STORE DOCUMENT
     |================================================================*/
    public function store(string $tenant, Request $request)
    {
        $data = $request->validate([
            'title'                   => 'required|string|max:200',
            'description'             => 'nullable|string',
            'folder_id'               => 'nullable|exists:document_folders,id',
            'category'                => 'required|in:policy,contract,template,form,handbook,other',
            'visibility'              => 'required|in:public,private,role_based,employee_specific',
            'expiry_date'             => 'nullable|date',
            'requires_acknowledgment' => 'boolean',
            'requires_signature'      => 'boolean',
            'file'                    => 'required|file|max:51200', // 50MB
        ]);

        $file     = $request->file('file');
        $fileSize = $file->getSize();

        // Enforce plan storage limit
        $currentTenant = Tenant::where('slug', $tenant)->first();
        if ($currentTenant && ! $currentTenant->canUploadBytes($fileSize)) {
            $maxGb = $currentTenant->getPlanLimit('max_storage_gb');
            return back()->withInput()->with('error',
                "You have reached your {$maxGb}GB storage limit. Please upgrade your plan or delete existing files.");
        }

        $path = $file->store('documents', 'public');

        Document::create([
            'folder_id'               => $data['folder_id'] ?? null,
            'uploaded_by'             => auth()->id(),
            'title'                   => $data['title'],
            'description'             => $data['description'] ?? null,
            'file_path'               => $path,
            'file_name'               => $file->getClientOriginalName(),
            'mime_type'               => $file->getMimeType(),
            'file_size'               => $fileSize,
            'version'                 => '1.0',
            'category'                => $data['category'],
            'visibility'              => $data['visibility'],
            'expiry_date'             => $data['expiry_date'] ?? null,
            'requires_acknowledgment' => $data['requires_acknowledgment'] ?? false,
            'requires_signature'      => $data['requires_signature'] ?? false,
            'download_count'          => 0,
        ]);

        // Track storage usage on main tenant record
        if ($currentTenant) {
            $currentTenant->incrementStorage($fileSize);
        }

        return back()->with('success', 'Document uploaded successfully.');
    }

    /* ================================================================
     | DOWNLOAD
     |================================================================*/
    public function download(string $tenant, Document $document)
    {
        if (! Storage::disk('public')->exists($document->file_path)) {
            return back()->with('error', 'File not found.');
        }

        $document->increment('download_count');

        return Storage::disk('public')->download(
            $document->file_path,
            $document->file_name
        );
    }

    /* ================================================================
     | DESTROY
     |================================================================*/
    public function destroy(string $tenant, Document $document)
    {
        $fileSize      = $document->file_size;
        $currentTenant = Tenant::where('slug', $tenant)->first();

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        $currentTenant?->decrementStorage($fileSize);

        return back()->with('success', 'Document deleted.');
    }

    /* ================================================================
     | FOLDERS — CRUD
     |================================================================*/
    public function storeFolder(string $tenant, Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'parent_id'  => 'nullable|exists:document_folders,id',
            'color'      => 'nullable|string|max:7',
            'icon'       => 'nullable|string|max:50',
            'visibility' => 'required|in:public,private,role_based',
        ]);

        DocumentFolder::create(array_merge($data, ['created_by' => auth()->id()]));
        return back()->with('success', 'Folder created.');
    }

    public function destroyFolder(string $tenant, DocumentFolder $folder)
    {
        if ($folder->documents()->count() > 0 || $folder->children()->count() > 0) {
            return back()->with('error', 'Cannot delete a folder that contains documents or sub-folders.');
        }
        $folder->delete();
        return back()->with('success', 'Folder deleted.');
    }

    /* ================================================================
     | SEND FOR SIGNATURE / ACKNOWLEDGMENT
     |================================================================*/
    public function sendForSignature(string $tenant, Request $request, Document $document)
    {
        $request->validate([
            'employee_ids'   => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        $sent = 0;
        foreach ($request->employee_ids as $empId) {
            $existing = DocumentSignature::where('document_id', $document->id)
                ->where('employee_id', $empId)->exists();

            if (! $existing) {
                // Create pending signature record
                DocumentSignature::create([
                    'document_id' => $document->id,
                    'employee_id' => $empId,
                    'action'      => 'acknowledged',
                    'signed_at'   => null,
                ]);
                $sent++;
            }
        }

        return back()->with('success', "Document sent to {$sent} employee(s) for signature.");
    }

    /* ================================================================
     | MARK ACKNOWLEDGED
     |================================================================*/
    public function acknowledge(string $tenant, Request $request, Document $document)
    {
        $request->validate(['employee_id' => 'required|exists:employees,id']);

        DocumentSignature::updateOrCreate(
            ['document_id' => $document->id, 'employee_id' => $request->employee_id],
            [
                'action'     => 'acknowledged',
                'signed_at'  => now(),
                'ip_address' => $request->ip(),
            ]
        );

        return back()->with('success', 'Document acknowledged.');
    }
}