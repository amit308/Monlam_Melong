<?php

namespace App\Http\Controllers;

use App\Models\MonlamMelongFinetuning;
use App\Models\Category;
use App\Models\Tag;
use App\Models\EntryActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class MonlamMelongFinetuningController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        // Authentication will be handled through routes/middleware
    }

    /**
     * Difficulty levels for the application.
     */
    const DIFFICULTY_LEVELS = [
        1 => 'Easy',
        2 => 'Fairly Easy',
        3 => 'Medium',
        4 => 'Fairly Hard',
        5 => 'Hard',
    ];

    /**
     * Display a listing of the entries based on user role.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $question = trim((string) $request->get('question', ''));

        // Admin and Chief Editor can see all entries
        if ($user->isAdmin() || $user->isChiefEditor()) {
            $entries = MonlamMelongFinetuning::with('user')
                ->when($request->category, function($query) use ($request) {
                    return $query->where('category', $request->category);
                })
                ->when($request->status, function($query) use ($request) {
                    return $query->where('status', $request->status);
                })
                ->when($question !== '', function($query) use ($question) {
                    return $query->where('question', 'like', '%' . $question . '%');
                })
                ->when($request->author && $request->user()->isAdmin(), function($query) use ($request) {
                    return $query->where('user_id', $request->author);
                })
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        }
        // Reviewer can see entries pending approval or approved
        else if ($user->isReviewer()) {
            $entries = MonlamMelongFinetuning::with('user')
                ->whereIn('status', ['pending', 'approved', 'rejected'])
                ->when($request->category, function($query) use ($request) {
                    return $query->where('category', $request->category);
                })
                ->when($request->status, function($query) use ($request) {
                    return $query->where('status', $request->status);
                })
                ->when($question !== '', function($query) use ($question) {
                    return $query->where('question', 'like', '%' . $question . '%');
                })
                ->when($request->author && $request->user()->isAdmin(), function($query) use ($request) {
                    return $query->where('user_id', $request->author);
                })
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        }
        // Editor can only see entries in their allowed categories
        else {
            $query = MonlamMelongFinetuning::with('user')
                ->where('user_id', $user->id);
            
            // If editor has category restrictions and no specific category requested
            if (!empty($user->allowed_categories) && !$request->category) {
                $query->whereIn('category', $user->allowed_categories);
            }
            // If specific category requested, check if user can access it
            elseif ($request->category && !$user->canAccessCategory($request->category)) {
                abort(403, 'You do not have access to this category.');
            }
            elseif ($request->category) {
                $query->where('category', $request->category);
            }
            
            // Apply status filter if provided
            if ($request->status) {
                $query->where('status', $request->status);
            }

            // Apply question search if provided
            if ($question !== '') {
                $query->where('question', 'like', '%' . $question . '%');
            }
            
            // Apply author filter if provided and user is admin
            if ($request->author && $user->isAdmin()) {
                $query->where('user_id', $request->author);
            }
            
            $entries = $query->orderBy('created_at', 'desc')->paginate(10);
        }

        // Get unique categories and tags for filtering
        $categories = $this->getUniqueCategories();
        $tags = $this->getUniqueTags();
        
        // Get unique authors for the filter
        // If a category is selected, only show authors who have entries in that category
        $authorsQuery = \App\Models\User::whereHas('entries');
        
        if ($request->category) {
            $authorsQuery->whereHas('entries', function($query) use ($request) {
                $query->where('category', $request->category);
            });
        }
        
        $authors = $authorsQuery->select('id', 'name')->get();

        // Get current filter values
        $filters = [
            'category' => $request->category,
            'status' => $request->status,
            'author' => $request->author,
            'question' => $question !== '' ? $question : null,
            'hasFilters' => ($request->has('category') || $request->has('status') || $request->has('author') || $question !== '')
        ];

        // If this is an AJAX request for authors, return JSON data
        if ($request->ajax() || $request->has('ajax')) {
            return response()->json([
                'authors' => $authors->map(function($author) {
                    return [
                        'id' => $author->id,
                        'name' => $author->name
                    ];
                })->toArray()
            ]);
        }

        return view('entries.index', compact('entries', 'categories', 'tags', 'authors', 'filters'));
    }

    /**
     * Display the specified entry.
     */
    public function show(MonlamMelongFinetuning $entry)
    {
        // Check if user has permission to view this entry
        if (!$this->canViewEntry($entry)) {
            abort(403, 'You do not have permission to view this entry.');
        }

        return view('entries.show', compact('entry'));
    }
    
    /**
     * Show the form for creating a new entry.
     */
    public function create()
    {
        // Only admins and editors can create entries
        if (!Auth::user()->isAdmin() && !Auth::user()->isEditor()) {
            abort(403, 'Unauthorized action.');
        }

        // Get categories and tags for dropdowns
        $categories = $this->getUniqueCategories();
        $tags = $this->getUniqueTags();
        $difficultyLevels = self::DIFFICULTY_LEVELS;

        return view('entries.create', compact('categories', 'tags', 'difficultyLevels'));
    }

    /**
     * Store a newly created entry.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Only admins and editors can create entries
        if (!$user->isAdmin() && !$user->isEditor()) {
            abort(403, 'Unauthorized action.');
        }
        
        // Check if the user has permission for the selected category
        if (!$user->isAdmin() && !$user->canAccessCategory($request->category)) {
            abort(403, 'You do not have permission to create entries in this category.');
        }

        $validated = $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'context' => 'required|string',
            'reference' => 'nullable|string',
            'category' => 'nullable|string|max:50',
            'difficulty' => 'nullable|integer|min:1|max:5',
            'tags' => 'nullable|string|max:255',
        ]);

        // Save the raw tags string temporarily 
        $tagsInput = isset($validated['tags']) ? $validated['tags'] : null;
        
        // Create entry without tags first
        $entry = new MonlamMelongFinetuning($validated);
        $entry->user_id = Auth::id();
        $entry->status = 'draft';
        $entry->save();

        // Process tags and associate them with the entry
        if (!empty($tagsInput)) {
            $this->syncTagsWithEntry($entry, $tagsInput);
        }

        // Log activity: creation words
        $words = $this->countWords(($validated['question'] ?? '') . ' ' . ($validated['answer'] ?? ''));
        EntryActivityLog::create([
            'user_id' => $user->id,
            'entry_id' => $entry->id,
            'action' => 'created',
            'words_created' => $words,
            'words_edited' => 0,
            'category' => $entry->category,
            'occurred_at' => now(),
        ]);

        return redirect()->route('entries.show', $entry)
            ->with('success', 'Entry created successfully');
    }

    // Second show method removed to fix duplicate method declaration

    /**
     * Show the form for editing the specified entry.
     */
    public function edit(MonlamMelongFinetuning $entry)
    {
        // Check if user has permission to edit this entry
        if (!$this->canEditEntry($entry)) {
            abort(403, 'Unauthorized action.');
        }

        // Get categories and tags for dropdowns
        $categories = $this->getUniqueCategories();
        $tags = $this->getUniqueTags();
        $difficultyLevels = self::DIFFICULTY_LEVELS;

        // Convert tags from comma-separated string to array
        $entryTags = $entry->tags ? explode(',', $entry->tags) : [];

        return view('entries.edit', compact('entry', 'categories', 'tags', 'difficultyLevels', 'entryTags'));
    }

    /**
     * Update the specified entry in storage.
     */
    public function update(Request $request, MonlamMelongFinetuning $entry)
    {
        if (!$this->canEditEntry($entry)) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'context' => 'required|string',
            'reference' => 'nullable|string',
            'category' => 'nullable|string|max:50',
            'difficulty' => 'nullable|integer|min:1|max:5',
            'tags' => 'nullable|string|max:255',
            'selected_tags' => 'nullable|array',
            'selected_tags.*' => 'nullable|string',
            'status' => 'nullable|string|in:draft,pending,approved,rejected',
        ]);

        // Process tags from both checkboxes and text input
        $selectedTags = $request->input('selected_tags', []);
        $newTagsInput = $request->input('tags', '');
        
        // Prepare combined tags input
        $allTagsInput = '';
        
        if (!empty($selectedTags)) {
            $allTagsInput = implode(',', array_filter($selectedTags));
        }
        
        if (!empty($newTagsInput)) {
            $allTagsInput = $allTagsInput ? $allTagsInput . ',' . $newTagsInput : $newTagsInput;
        }
        
        // Store the combined tags string for display purposes
        if (!empty($allTagsInput)) {
            $validated['tags'] = $this->processTagsInput($allTagsInput);
        } else {
            $validated['tags'] = null;
        }
        
        // Only allow status change if user has review permissions
        if (!Auth::user()->canReviewContent()) {
            unset($validated['status']);
        }

        // For approved/rejected entries, check if status can be changed within 10-minute window
        if (isset($validated['status']) && in_array($entry->status, ['approved', 'rejected'])) {
            if (!$entry->canChangeStatus()) {
                return redirect()->back()->with('error', 'Cannot change status. The 10-minute editing window has expired or status has already been edited.');
            }
            
            // Mark that status has been edited in this window
            $validated['status_edited_in_window'] = true;
        }

        // Calculate word edit delta before update
        $oldWords = $this->countWords(($entry->question ?? '') . ' ' . ($entry->answer ?? ''));

        $entry->update($validated);
        $newWords = $this->countWords(($entry->question ?? '') . ' ' . ($entry->answer ?? ''));
        $edited = abs($newWords - $oldWords);

        if ($edited > 0) {
            EntryActivityLog::create([
                'user_id' => Auth::id(),
                'entry_id' => $entry->id,
                'action' => 'edited',
                'words_created' => 0,
                'words_edited' => $edited,
                'category' => $entry->category,
                'occurred_at' => now(),
            ]);
        }
        
        // Sync tags with entry in the tags table and pivot table
        if (isset($allTagsInput)) {
            $this->syncTagsWithEntry($entry, $allTagsInput);
        }

        return redirect()->route('entries.show', $entry)
            ->with('success', 'Entry updated successfully');
    }

    /**
     * Remove the specified entry from storage.
     */
    public function destroy(MonlamMelongFinetuning $entry)
    {
        // Only admin, chief editor or the owner can delete an entry
        if (!Auth::user()->isAdmin() && !Auth::user()->isChiefEditor() && Auth::id() !== $entry->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $entry->delete();

        return redirect()->route('entries.index')
            ->with('success', 'Entry deleted successfully');
    }

    /**
     * Submit entry for review.
     */
    public function submitForReview(MonlamMelongFinetuning $entry)
    {
        // Only owner, admin, or chief editor can submit for review
        if (!Auth::user()->isAdmin() && !Auth::user()->isChiefEditor() && Auth::id() !== $entry->user_id) {
            abort(403, 'Unauthorized action.');
        }

        // Can submit both draft and rejected entries
        if ($entry->status !== 'draft' && $entry->status !== 'rejected') {
            return redirect()->back()->with('error', 'Only draft or rejected entries can be submitted for review');
        }

        $entry->status = 'pending';
        $entry->save();

        return redirect()->route('entries.show', $entry)
            ->with('success', 'Entry submitted for review');
    }

    /**
     * Submit entry for review via GET request.
     */
    public function submitViaGet(MonlamMelongFinetuning $entry)
    {
        // Only owner, admin, or chief editor can submit for review
        if (!Auth::user()->isAdmin() && !Auth::user()->isChiefEditor() && Auth::id() !== $entry->user_id) {
            abort(403, 'Unauthorized action.');
        }

        // Can submit both draft and rejected entries
        if ($entry->status !== 'draft' && $entry->status !== 'rejected') {
            return redirect()->back()->with('error', 'Only draft or rejected entries can be submitted for review');
        }

        $entry->status = 'pending';
        $entry->save();

        return redirect()->route('entries.show', $entry)
            ->with('success', 'Entry submitted for review');
    }

    /**
     * Show entries pending review.
     */
    public function reviewQueue(Request $request)
    {
        $user = Auth::user();
        $question = trim((string) $request->get('question', ''));

        // Only admin, chief editors, and reviewers can access review queue
        if (!$user->canReviewContent()) {
            abort(403, 'You do not have permission to access the review queue');
        }

        // Get all pending entries
        $pendingEntries = MonlamMelongFinetuning::with('user')
            ->where('status', 'pending')
            ->when($request->category, function($query) use ($request) {
                return $query->where('category', $request->category);
            })
            ->when($question !== '', function($query) use ($question) {
                return $query->where('question', 'like', '%' . $question . '%');
            })
            ->when($request->author && $user->isAdmin(), function($query) use ($request) {
                return $query->where('user_id', $request->author);
            })
            ->orderBy('updated_at', 'asc') // Oldest submissions first
            ->paginate(10);

        // Get categories for filter
        $categories = $this->getUniqueCategories();

        // Get unique authors for the filter (only for admins)
        // If a category is selected, only show authors who have pending entries in that category
        $authors = collect();
        if ($user->isAdmin()) {
            $authorsQuery = \App\Models\User::whereHas('entries', function($query) {
                $query->where('status', 'pending');
            });
            
            if ($request->category) {
                $authorsQuery->whereHas('entries', function($query) use ($request) {
                    $query->where('status', 'pending')
                          ->where('category', $request->category);
                });
            }
            
            $authors = $authorsQuery->select('id', 'name')->get();
        }

        // Get current filter values
        $filters = [
            'category' => $request->category,
            'author' => $request->author,
            'question' => $question !== '' ? $question : null,
            'hasFilters' => ($request->has('category') || $request->has('author') || $question !== '')
        ];

        // If this is an AJAX request for authors, return JSON data
        if ($request->ajax() || $request->has('ajax')) {
            return response()->json([
                'authors' => $authors->map(function($author) {
                    return [
                        'id' => $author->id,
                        'name' => $author->name
                    ];
                })->toArray()
            ]);
        }

        return view('entries.review-queue', compact('pendingEntries', 'categories', 'authors', 'filters'));
    }

    /**
     * Review an entry (approve or reject).
     */
    public function review(Request $request, MonlamMelongFinetuning $entry)
    {
        $user = Auth::user();
        
        // Only those with review permissions can review
        if (!$user->canReviewContent()) {
            abort(403, 'You do not have permission to review entries.');
        }

        // Validate status (approved or rejected)
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'feedback' => 'nullable|string',
        ]);

        $entry->status = $validated['status'];
        $entry->status_updated_at = now();
        $entry->status_edited_in_window = false; // Reset the flag for new status
        
        // Save feedback if provided (especially important for rejected entries)
        if (isset($validated['feedback'])) {
            $entry->feedback = $validated['feedback'];
        }
        
        $entry->save();

        return redirect()->route('entries.show', $entry)
            ->with('success', 'Entry ' . $validated['status'] . ' successfully');
    }

    /**
     * Check if user can view an entry.
     */
    private function canViewEntry(MonlamMelongFinetuning $entry)
    {
        $user = Auth::user();

        // Admin and Chief Editor can view all entries
        if ($user->isAdmin() || $user->isChiefEditor()) {
            return true;
        }

        // Reviewer can view entries pending approval or approved
        if ($user->isReviewer() && in_array($entry->status, ['pending', 'approved', 'rejected'])) {
            return true;
        }

        // Editor can only view their own entries and only in their allowed categories
        if ($entry->user_id === $user->id && $user->canAccessCategory($entry->category)) {
            return true;
        }
        
        return false;
    }

    /**
     * Check if user can edit an entry.
     */
    private function canEditEntry(MonlamMelongFinetuning $entry)
    {
        $user = Auth::user();

        // Admin and Chief Editor can edit all entries
        if ($user->isAdmin() || $user->isChiefEditor()) {
            return true;
        }

        // Reviewer can review pending entries but not edit content
        if ($user->isReviewer()) {
            return false;
        }

        // Editor can edit their own entries if they're not approved/rejected OR within 10-minute window
        if ($entry->user_id === $user->id && $user->canAccessCategory($entry->category)) {
            // Allow editing if status is not approved/rejected
            if (!in_array($entry->status, ['approved', 'rejected'])) {
            return true;
            }
            
            // Allow editing if within 10-minute window for approved/rejected entries
            if ($entry->canChangeStatus()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get categories including predefined Tibetan categories and user-added ones.
     * Filtered by user permissions if user is not an admin.
     * Now with caching for improved performance.
     */
    public function getUniqueCategories()
    {
        $user = Auth::user();
        
        // Create a cache key that includes user ID to ensure proper permissions
        $cacheKey = "categories_for_user_{$user->id}";
        
        // Cache categories for 10 minutes
        return Cache::remember($cacheKey, 600, function () use ($user) {
            // Get all categories from the database
            $dbCategories = Category::orderBy('name')->get();
            
            // If user is not an admin or chief editor, filter by allowed categories
            if (!$user->isAdmin() && !$user->isChiefEditor() && !empty($user->allowed_categories)) {
                $dbCategories = $dbCategories->filter(function($category) use ($user) {
                    return in_array($category->name, $user->allowed_categories);
                });
            }
            
            return $dbCategories;
        });
    }

    /**
     * Get unique tags from all entries.
     * Now with caching for improved performance.
     */
    private function getUniqueTags()
    {
        // Cache tags for 5 minutes as they might change more often than categories
        return Cache::remember('all_tags', 300, function () {
            return Tag::orderBy('name')->get();
        });
    }

    /**
     * Display a listing of the categories.
     * No caching to ensure latest data is always shown.
     */
    public function categoryIndex()
    {
        // This method can only be accessed by admin (enforced by middleware)
        
        // Get all categories directly from database
        $categories = Category::orderBy('name')->get();
        
        return view('admin.categories.index', compact('categories'));
    }
    
    /**
     * Store a newly created category.
     */
    public function categoryStore(Request $request)
    {
        // Only admin can access this method (enforced by middleware)
        $request->validate([
            'name' => 'required|string|max:255',
            'tibetan_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);
        
        $categoryName = trim($request->name);
        
        // Check if category already exists
        if (Category::where('name', $categoryName)->exists()) {
            return redirect()->route('admin.categories.index')
                ->with('error', 'Category already exists.');
        }
        
        // Create new category in the categories table
        Category::create([
            'name' => $categoryName,
            'tibetan_name' => $request->tibetan_name ?? null,
            'description' => $request->description ?? null,
            'is_predefined' => false,
        ]);
        
        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }
    
    /**
     * Update the specified category.
     */
    public function categoryUpdate(Request $request, $id)
    {
        // Only admin can access this method (enforced by middleware)
        $request->validate([
            'name' => 'required|string|max:255',
            'tibetan_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);
        
        $newName = trim($request->name);
        
        // Find the category to update
        $categoryModel = Category::find($id);
        
        if (!$categoryModel) {
            return redirect()->route('admin.categories.index')
                ->with('error', 'Category not found.');
        }
        
        $currentName = $categoryModel->name;
        
        // Check if predefined and protect from name changes
        if ($categoryModel->is_predefined && $currentName !== $newName) {
            return redirect()->route('admin.categories.index')
                ->with('error', 'Predefined categories cannot be renamed.');
        }
        
        // Check if new category name already exists (unless it's the same as current)
        if ($currentName !== $newName && Category::where('name', $newName)->where('id', '!=', $id)->exists()) {
            return redirect()->route('admin.categories.index')
                ->with('error', 'Category name already exists.');
        }
        
        // Update the category
        $categoryModel->name = $newName;
        $categoryModel->tibetan_name = $request->tibetan_name;
        $categoryModel->description = $request->description;
        $categoryModel->save();
        
        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }
    
    /**
     * Remove the specified category.
     */
    public function categoryDestroy($id)
    {
        // Only admin can access this method (enforced by middleware)
        
        // Find the category to delete
        $categoryModel = Category::find($id);
        
        if (!$categoryModel) {
            return redirect()->route('admin.categories.index')
                ->with('error', 'Category not found.');
        }
        
        $categoryName = $categoryModel->name;
        
        // Check if category is in use by entries
        $entriesCount = $categoryModel->entries()->count();
            
        if ($entriesCount > 0) {
            return redirect()->route('admin.categories.index')
                ->with('error', "Cannot delete category '{$categoryName}' that is in use by {$entriesCount} entries.");
        }
        
        // Delete the category
        $categoryModel->delete();
        
    return view('admin.categories.index', compact('categories'));
}

    
/**
 * Display a listing of the tags.
 * No caching to ensure latest data is always shown.
 */
public function tagIndex()
{
    // This method can only be accessed by admin (enforced by middleware)
        
    // Get all tags directly from database
    $tags = Tag::orderBy('name')->get();
        
    return view('admin.tags.index', compact('tags'));
}
    
    /**
     * Store a newly created tag.
     */
    public function tagStore(Request $request)
    {
        // Only admin can access this method (enforced by middleware)
        
        $request->validate([
            'name' => 'required|string|max:255',
            'tibetan_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);
        
        $name = $request->name;
        
        // Check if tag already exists
        $existingTag = Tag::where('name', $name)->first();
        
        if ($existingTag) {
            return redirect()->route('admin.tags.index')
                ->with('error', "Tag '{$name}' already exists.");
        }
        
        // Create new tag
        Tag::create([
            'name' => $name,
            'tibetan_name' => $request->tibetan_name,
            'description' => $request->description,
        ]);
        
        return redirect()->route('admin.tags.index')
            ->with('success', "Tag '{$name}' added successfully.");
    }
    
    /**
     * Update the specified tag.
     */
    public function tagUpdate(Request $request, $id)
    {
        // Only admin can access this method (enforced by middleware)
        
        $request->validate([
            'name' => 'required|string|max:255',
            'tibetan_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);
        
        $newName = $request->name;
        
        // Find tag by id
        $tagModel = Tag::find($id);
        
        if (!$tagModel) {
            return redirect()->route('admin.tags.index')
                ->with('error', 'Tag not found.');
        }
        
        $oldName = $tagModel->name;
        
        // Check if the new tag name already exists (except for the current tag)
        if ($oldName !== $newName) {
            $existingTag = Tag::where('name', $newName)
                             ->where('id', '!=', $id)
                             ->first();
            
            if ($existingTag) {
                return redirect()->route('admin.tags.index')
                    ->with('error', "Tag '{$newName}' already exists.");
            }
        }
        
        // Update tag
        $tagModel->name = $newName;
        $tagModel->tibetan_name = $request->tibetan_name;
        $tagModel->description = $request->description;
        $tagModel->save();
        
        return redirect()->route('admin.tags.index')
            ->with('success', "Tag '{$oldName}' updated successfully.");
    }
    
    /**
     * Remove the specified tag.
     */
    public function tagDestroy($id)
    {
        // Only admin can access this method (enforced by middleware)
        
        // Find the tag to delete
        $tagModel = Tag::find($id);
        
        if (!$tagModel) {
            return redirect()->route('admin.tags.index')
                ->with('error', 'Tag not found.');
        }
        
        $tagName = $tagModel->name;
        
        // Check if tag is in use by entries
        $entriesCount = $tagModel->entries()->count();
            
        if ($entriesCount > 0) {
            return redirect()->route('admin.tags.index')
                ->with('error', "Cannot delete tag '{$tagName}' that is in use by {$entriesCount} entries.");
        }
        
        // Delete the tag
        $tagModel->delete();
        
        return redirect()->route('admin.tags.index')
            ->with('success', "Tag '{$tagName}' deleted successfully.");
    }
    
    /**
     * Process tags input from form.
     */
    private function processTagsInput($tagsInput)
    {
        if (empty($tagsInput)) {
            return null;
        }

        // Split by comma, trim whitespace, remove empty values, and make unique
        $tags = collect(explode(',', $tagsInput))
            ->map(function($tag) { return trim($tag); })
            ->filter()
            ->unique()
            ->values();

        return $tags->isEmpty() ? null : $tags->implode(',');
    }
    
    /**
     * Sync tags with an entry.
     * This will create tags that don't exist in the database
     * and associate all tags with the entry.
     * 
     * @param MonlamMelongFinetuning $entry The entry to associate tags with
     * @param string $tagsInput Comma-separated string of tag names
     */
    private function syncTagsWithEntry(MonlamMelongFinetuning $entry, $tagsInput)
    {
        // No tags to sync
        if (empty($tagsInput)) {
            return;
        }
        
        // Process tag names into an array
        $tagNames = collect(explode(',', $tagsInput))
            ->map(function($tag) { return trim($tag); })
            ->filter()
            ->unique()
            ->values()
            ->toArray();
            
        // Tag IDs to sync with entry
        $tagIds = [];
        
        // Process each tag name
        foreach ($tagNames as $tagName) {
            // Skip empty tag names
            if (empty($tagName)) continue;
            
            // Find or create tag
            $tag = Tag::firstOrCreate(
                ['name' => $tagName],
                ['description' => ''] // Default empty description
            );
            
            $tagIds[] = $tag->id;
        }
        
        // Associate tags with entry
        $entry->tags()->sync($tagIds);
        
        // Also store tag names as comma-separated string in tags column for backwards compatibility
        $entry->tags = implode(',', $tagNames);
        $entry->save();
    }

    /**
     * Count words from a given string by tokenizing on whitespace and punctuation.
     */
    private function countWords(string $text): int
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        if ($text === '') return 0;
        // Split on whitespace; works for Tibetan/Latin sequences as tokens
        $tokens = preg_split('/\s+/u', $text);
        return is_array($tokens) ? count($tokens) : 0;
    }
}
