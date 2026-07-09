import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StudentNoteController::index
* @see app/Http/Controllers/StudentNoteController.php:20
* @route '/{current_team}/notes'
*/
export const index = (args: { current_team: string | number } | [current_team: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/{current_team}/notes',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StudentNoteController::index
* @see app/Http/Controllers/StudentNoteController.php:20
* @route '/{current_team}/notes'
*/
index.url = (args: { current_team: string | number } | [current_team: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { current_team: args }
    }

    if (Array.isArray(args)) {
        args = {
            current_team: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        current_team: args.current_team,
    }

    return index.definition.url
            .replace('{current_team}', parsedArgs.current_team.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StudentNoteController::index
* @see app/Http/Controllers/StudentNoteController.php:20
* @route '/{current_team}/notes'
*/
index.get = (args: { current_team: string | number } | [current_team: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StudentNoteController::index
* @see app/Http/Controllers/StudentNoteController.php:20
* @route '/{current_team}/notes'
*/
index.head = (args: { current_team: string | number } | [current_team: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StudentNoteController::index
* @see app/Http/Controllers/StudentNoteController.php:20
* @route '/{current_team}/notes'
*/
const indexForm = (args: { current_team: string | number } | [current_team: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StudentNoteController::index
* @see app/Http/Controllers/StudentNoteController.php:20
* @route '/{current_team}/notes'
*/
indexForm.get = (args: { current_team: string | number } | [current_team: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StudentNoteController::index
* @see app/Http/Controllers/StudentNoteController.php:20
* @route '/{current_team}/notes'
*/
indexForm.head = (args: { current_team: string | number } | [current_team: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\StudentNoteController::store
* @see app/Http/Controllers/StudentNoteController.php:69
* @route '/{current_team}/notes'
*/
export const store = (args: { current_team: string | number } | [current_team: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/{current_team}/notes',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StudentNoteController::store
* @see app/Http/Controllers/StudentNoteController.php:69
* @route '/{current_team}/notes'
*/
store.url = (args: { current_team: string | number } | [current_team: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { current_team: args }
    }

    if (Array.isArray(args)) {
        args = {
            current_team: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        current_team: args.current_team,
    }

    return store.definition.url
            .replace('{current_team}', parsedArgs.current_team.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StudentNoteController::store
* @see app/Http/Controllers/StudentNoteController.php:69
* @route '/{current_team}/notes'
*/
store.post = (args: { current_team: string | number } | [current_team: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StudentNoteController::store
* @see app/Http/Controllers/StudentNoteController.php:69
* @route '/{current_team}/notes'
*/
const storeForm = (args: { current_team: string | number } | [current_team: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StudentNoteController::store
* @see app/Http/Controllers/StudentNoteController.php:69
* @route '/{current_team}/notes'
*/
storeForm.post = (args: { current_team: string | number } | [current_team: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\StudentNoteController::update
* @see app/Http/Controllers/StudentNoteController.php:87
* @route '/{current_team}/notes/{studentNote}'
*/
export const update = (args: { current_team: string | number, studentNote: number | { id: number } } | [current_team: string | number, studentNote: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/{current_team}/notes/{studentNote}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\StudentNoteController::update
* @see app/Http/Controllers/StudentNoteController.php:87
* @route '/{current_team}/notes/{studentNote}'
*/
update.url = (args: { current_team: string | number, studentNote: number | { id: number } } | [current_team: string | number, studentNote: number | { id: number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            current_team: args[0],
            studentNote: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        current_team: args.current_team,
        studentNote: typeof args.studentNote === 'object'
        ? args.studentNote.id
        : args.studentNote,
    }

    return update.definition.url
            .replace('{current_team}', parsedArgs.current_team.toString())
            .replace('{studentNote}', parsedArgs.studentNote.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StudentNoteController::update
* @see app/Http/Controllers/StudentNoteController.php:87
* @route '/{current_team}/notes/{studentNote}'
*/
update.patch = (args: { current_team: string | number, studentNote: number | { id: number } } | [current_team: string | number, studentNote: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\StudentNoteController::update
* @see app/Http/Controllers/StudentNoteController.php:87
* @route '/{current_team}/notes/{studentNote}'
*/
const updateForm = (args: { current_team: string | number, studentNote: number | { id: number } } | [current_team: string | number, studentNote: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StudentNoteController::update
* @see app/Http/Controllers/StudentNoteController.php:87
* @route '/{current_team}/notes/{studentNote}'
*/
updateForm.patch = (args: { current_team: string | number, studentNote: number | { id: number } } | [current_team: string | number, studentNote: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

/**
* @see \App\Http\Controllers\StudentNoteController::destroy
* @see app/Http/Controllers/StudentNoteController.php:105
* @route '/{current_team}/notes/{studentNote}'
*/
export const destroy = (args: { current_team: string | number, studentNote: number | { id: number } } | [current_team: string | number, studentNote: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/{current_team}/notes/{studentNote}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\StudentNoteController::destroy
* @see app/Http/Controllers/StudentNoteController.php:105
* @route '/{current_team}/notes/{studentNote}'
*/
destroy.url = (args: { current_team: string | number, studentNote: number | { id: number } } | [current_team: string | number, studentNote: number | { id: number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            current_team: args[0],
            studentNote: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        current_team: args.current_team,
        studentNote: typeof args.studentNote === 'object'
        ? args.studentNote.id
        : args.studentNote,
    }

    return destroy.definition.url
            .replace('{current_team}', parsedArgs.current_team.toString())
            .replace('{studentNote}', parsedArgs.studentNote.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StudentNoteController::destroy
* @see app/Http/Controllers/StudentNoteController.php:105
* @route '/{current_team}/notes/{studentNote}'
*/
destroy.delete = (args: { current_team: string | number, studentNote: number | { id: number } } | [current_team: string | number, studentNote: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\StudentNoteController::destroy
* @see app/Http/Controllers/StudentNoteController.php:105
* @route '/{current_team}/notes/{studentNote}'
*/
const destroyForm = (args: { current_team: string | number, studentNote: number | { id: number } } | [current_team: string | number, studentNote: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StudentNoteController::destroy
* @see app/Http/Controllers/StudentNoteController.php:105
* @route '/{current_team}/notes/{studentNote}'
*/
destroyForm.delete = (args: { current_team: string | number, studentNote: number | { id: number } } | [current_team: string | number, studentNote: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

const StudentNoteController = { index, store, update, destroy }

export default StudentNoteController