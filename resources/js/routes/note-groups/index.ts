import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\StudentNoteGroupController::store
* @see app/Http/Controllers/StudentNoteGroupController.php:16
* @route '/{current_team}/note-groups'
*/
export const store = (args: { current_team: string | number } | [current_team: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/{current_team}/note-groups',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StudentNoteGroupController::store
* @see app/Http/Controllers/StudentNoteGroupController.php:16
* @route '/{current_team}/note-groups'
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
* @see \App\Http\Controllers\StudentNoteGroupController::store
* @see app/Http/Controllers/StudentNoteGroupController.php:16
* @route '/{current_team}/note-groups'
*/
store.post = (args: { current_team: string | number } | [current_team: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StudentNoteGroupController::store
* @see app/Http/Controllers/StudentNoteGroupController.php:16
* @route '/{current_team}/note-groups'
*/
const storeForm = (args: { current_team: string | number } | [current_team: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StudentNoteGroupController::store
* @see app/Http/Controllers/StudentNoteGroupController.php:16
* @route '/{current_team}/note-groups'
*/
storeForm.post = (args: { current_team: string | number } | [current_team: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\StudentNoteGroupController::update
* @see app/Http/Controllers/StudentNoteGroupController.php:28
* @route '/{current_team}/note-groups/{studentNoteGroup}'
*/
export const update = (args: { current_team: string | number, studentNoteGroup: number | { id: number } } | [current_team: string | number, studentNoteGroup: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/{current_team}/note-groups/{studentNoteGroup}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\StudentNoteGroupController::update
* @see app/Http/Controllers/StudentNoteGroupController.php:28
* @route '/{current_team}/note-groups/{studentNoteGroup}'
*/
update.url = (args: { current_team: string | number, studentNoteGroup: number | { id: number } } | [current_team: string | number, studentNoteGroup: number | { id: number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            current_team: args[0],
            studentNoteGroup: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        current_team: args.current_team,
        studentNoteGroup: typeof args.studentNoteGroup === 'object'
        ? args.studentNoteGroup.id
        : args.studentNoteGroup,
    }

    return update.definition.url
            .replace('{current_team}', parsedArgs.current_team.toString())
            .replace('{studentNoteGroup}', parsedArgs.studentNoteGroup.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StudentNoteGroupController::update
* @see app/Http/Controllers/StudentNoteGroupController.php:28
* @route '/{current_team}/note-groups/{studentNoteGroup}'
*/
update.patch = (args: { current_team: string | number, studentNoteGroup: number | { id: number } } | [current_team: string | number, studentNoteGroup: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\StudentNoteGroupController::update
* @see app/Http/Controllers/StudentNoteGroupController.php:28
* @route '/{current_team}/note-groups/{studentNoteGroup}'
*/
const updateForm = (args: { current_team: string | number, studentNoteGroup: number | { id: number } } | [current_team: string | number, studentNoteGroup: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StudentNoteGroupController::update
* @see app/Http/Controllers/StudentNoteGroupController.php:28
* @route '/{current_team}/note-groups/{studentNoteGroup}'
*/
updateForm.patch = (args: { current_team: string | number, studentNoteGroup: number | { id: number } } | [current_team: string | number, studentNoteGroup: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

const noteGroups = {
    store: Object.assign(store, store),
    update: Object.assign(update, update),
}

export default noteGroups