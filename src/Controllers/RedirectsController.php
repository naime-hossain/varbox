<?php

namespace Varbox\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Varbox\Contracts\RedirectFilterContract;
use Varbox\Contracts\RedirectModelContract;
use Varbox\Contracts\RedirectSortContract;
use Varbox\Models\Redirect;
use Varbox\Requests\RedirectRequest;
use Varbox\Traits\CanCrud;

class RedirectsController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    use CanCrud;

    /**
     * @var RedirectModelContract
     */
    protected $model;

    /**
     * RedirectsController constructor.
     *
     * @param RedirectModelContract $model
     */
    public function __construct(RedirectModelContract $model)
    {
        $this->model = $model;
    }

    /**
     * @param Request $request
     * @param RedirectFilterContract $filter
     * @param RedirectSortContract $sort
     * @return \Illuminate\View\View
     * @throws \Exception
     */
    public function index(Request $request, RedirectFilterContract $filter, RedirectSortContract $sort)
    {
        return $this->_index(function () use ($request, $filter, $sort) {
            $this->items = $this->model
                ->filtered($request->all(), $filter)
                ->sorted($request->all(), $sort)
                ->paginate(config('varbox.crud.per_page', 30));

            $this->title = 'Redirects';
            $this->view = view('varbox::admin.redirects.index');
            $this->vars = [
                'statuses' => $this->statusesToArray(),
            ];
        });
    }

    /**
     * @return \Illuminate\View\View
     * @throws \Exception
     */
    public function create()
    {
        return $this->_create(function () {
            $this->title = 'Add Redirect';
            $this->view = view('varbox::admin.redirects.add');
            $this->vars = [
                'statuses' => $this->statusesToArray(),
            ];
        });
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function store(Request $request)
    {
        app(config('varbox.bindings.form_requests.redirect_form_request', RedirectRequest::class));

        return $this->_store(function () use ($request) {
            $this->item = $this->model->create($request->all());
            $this->redirect = redirect()->route('admin.redirects.index');
        }, $request);
    }

    /**
     * @param RedirectModelContract $redirect
     * @return \Illuminate\View\View
     * @throws \Exception
     */
    public function edit(RedirectModelContract $redirect)
    {
        return $this->_edit(function () use ($redirect) {
            $this->item = $redirect;
            $this->title = 'Edit Redirect';
            $this->view = view('varbox::admin.redirects.edit');
            $this->vars = [
                'statuses' => $this->statusesToArray(),
            ];
        });
    }

    /**
     * @param Request $request
     * @param Redirect $redirect
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     */
    public function update(Request $request, Redirect $redirect)
    {
        app(config('varbox.bindings.form_requests.redirect_form_request', RedirectRequest::class));

        return $this->_update(function () use ($request, $redirect) {
            $this->item = $redirect;
            $this->redirect = redirect()->route('admin.redirects.index');

            $this->item->update($request->all());
        }, $request);
    }

    /**
     * @param RedirectModelContract $redirect
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(RedirectModelContract $redirect)
    {
        return $this->_destroy(function () use ($redirect) {
            $this->item = $redirect;
            $this->redirect = redirect()->route('admin.redirects.index');

            $this->item->delete();
        });
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function export()
    {
        try {
            $this->model->exportToFile();

            flash()->success('All redirects have been successfully exported to the "bootstrap/redirects.php" file!');
        } catch (\Exception $e) {
            flash()->error('Something went wrong! Please try again.', $e);
        }

        return redirect()->route('admin.redirects.index');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteAll()
    {
        try {
            $this->model->truncate();

            if ($this->model->shouldExportToFileAutomatically()) {
                $this->model->exportToFile();
            }

            flash()->success('All redirects have been successfully deleted!');
        } catch (\Exception $e) {
            flash()->error('Something went wrong! Please try again.', $e);
        }

        return redirect()->route('admin.redirects.index');
    }

    /**
     * @return array
     */
    protected function statusesToArray()
    {
        return (array)config('varbox.redirect.statuses', []);
    }
}
