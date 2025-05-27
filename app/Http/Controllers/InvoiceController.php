<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InvoiceItem;
use App\Models\Invoice;
use App\Helpers\Helper;
use App\Models\User;
use App\Models\Task;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    public function index(Request $request) {
        if ($request->ajax()) {
            $reqData = $request->all();

            $invoices = Invoice::with('items')
            ->when(isset($reqData['customer']), function ($builder) use ($reqData) {
                return $builder->where('customer_id', $reqData['customer']);
            })
            ->when(isset($reqData['month']), function ($builder) use ($reqData) {
                return $builder->where(\DB::raw("DATE_FORMAT(created_at, '%m')"), $reqData['month']);
            })
            ->when(isset($reqData['year']), function ($builder) use ($reqData) {
                return $builder->where(\DB::raw("DATE_FORMAT(created_at, '%Y')"), $reqData['year']);
            })
            ->when(isset($reqData['billcycle']), function ($builder) use ($reqData) {
                return $builder->where('billing_cycle', $reqData['billcycle']);
            })
            ->orderBy('id', isset($request['order'][0]['dir']) ? $request['order'][0]['dir'] : 'desc');

            return datatables()
                ->eloquent($invoices)
                ->addColumn('amount', function ($row) {
                    $total = 0;

                    foreach ($row->items as $item) {
                        $total += ($item->amount + $item->tax_amount);
                    }

                    return '$' . number_format($total, 2);
                })
                ->addColumn('action', function ($row) {
                    $action = '';

                    if (auth()->user()->can('invoices.show')) {
                        $action .= '<a href="'.route("invoices.show", encrypt($row->id)).'" class="btn btn-warning btn-sm me-2 btn-view"> <i class="bi bi-eye"></i> </a>';
                    }
    
                    if (auth()->user()->can('invoices.destroy')) {
                        $action .= '<form method="POST" action="'.route("invoices.destroy", encrypt($row->id)).'" style="display:inline;"><input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" value="'.csrf_token().'"><button type="submit" class="btn btn-danger btn-sm deleteGroup btn-delete"><i class="bi bi-trash"></i></button></form>';
                    }

                    if (auth()->user()->can('invoices.show')) {
                        $action .= '<a href="'.route("invoices.generate-pdf", encrypt($row->id)).'" class="btn btn-secondary btn-sm me-2 btn-pdf" style="margin-left:7px;"> <i class="bi bi-filetype-pdf"></i> </a>';
                    }

                    if (auth()->user()->can('credit-notes.add')) {
                        $action .= '<a href="'.route("credit-notes.add", encrypt($row->id)).'" class="btn btn-secondary btn-sm me-2 btn-pdf" style="margin-left:7px;"> <i class="bi bi-newspaper"></i> </a>';
                    }

                    return $action;
                })
                ->addColumn('cust', function ($row) {
                    return ($row->customer->name ?? '');
                })
                ->editColumn('created_at', function ($row) {
                    return date('d-m-Y', strtotime($row->created_at));
                })
                ->rawColumns(['action'])
                ->toJson();
        }

        $page_title = 'Invoices';

        return view('invoices.index', compact('page_title'));
    }

    public function invoiceRun(Request $request) {
        $customers = User::customer()->whereIn('id', explode(',', $request->users))->get();

        if ($customers->isEmpty()) {
            return response()->json(['status' => true, 'message' => 'Please check atleast a customer to proceed further!']);
        }

        \DB::beginTransaction();

        try {
            $invoiceGenerateFrom = '2025-03-15 00:00:00';

            foreach ($customers as $customer) {
                $dates = [$invoiceGenerateFrom, date('Y-m-d 23:59:59', strtotime($request->fromdate))];

                if (!empty($dates)) {
                        $tasks = Task::with('removal.rental')->where('customer_id', $customer->id)
                        ->whereDoesntHave('invoiceitem')
                        ->where('job_type', '!=', 0)
                        ->where(\DB::raw("DATE_FORMAT(task_date, '%Y-%m-%d')"), '>=', date('Y-m-d', strtotime($dates[0])))
                        ->where(\DB::raw("DATE_FORMAT(task_date, '%Y-%m-%d')"), '<=', date('Y-m-d', strtotime($dates[1])))
                        ->where('status', 5);

                        $initialDate = $tasks->clone()->orderBy('task_date', 'ASC')->first()->task_date ?? null;
                        $tasks = $tasks->get()->toArray();

                        if (isset($dates[0]) && isset($dates[1])) {

                            $tasksToBeConsidered = array_column($tasks, 'id');

                            if (!empty($tasks) && InvoiceItem::whereIn('task_id', $tasksToBeConsidered)->doesntExist()) {

                                $mainInvoice = Invoice::create([
                                    'code' => Helper::generateInvoiceNumber(),
                                    'customer_id' => $customer->id,
                                    'from' => $dates[0] . ' 00:00:00',
                                    'to' => $dates[1] . ' 23:59:59',
                                    'tax' => isset($tasks[0]['removal']['rental']['tax']) ? $tasks[0]['removal']['rental']['tax'] : 0,
                                    'billing_cycle' => $customer->billing_cycle
                                ]);
    
                                if ($mainInvoice) {
                                    $includedRental = [];
                                    
                                    foreach ($tasks as $task) {
                                        if (!isset($task['removal']['rental']['id'])) {
                                            \DB::rollBack();
                                            return response()->json(['status' => false, 'message' => 'No Rental found to generate invoice!']);
                                        }
                                        
                                        $finalTotalForTax = $thisRentalRate = 0;

                                        if (!isset($includedRental[$task['removal']['rental']['id']])) {
                                            $includedRental[$task['removal']['rental']['id']] = true;

                                            $myFromDate = Carbon::parse($initialDate)->format('Y-m-d 00:00:00');
                                            $myToDate = Carbon::parse($dates[1])->format('Y-m-d H:i:s');

                                            if (Carbon::parse($task['removal']['rental']['to_date'])->startOfDay()->lt(Carbon::parse($dates[1])->startOfDay())) {
                                                $myToDate = Carbon::parse($task['removal']['rental']['to_date'])->format('Y-m-d 23:59:59');
                                            }

                                            $totalDaysToCount = Carbon::parse($myFromDate)->diffInDays(Carbon::parse($myToDate));
                                            $getTotalWeekOrDaysCount = 1;

                                            if ($totalDaysToCount <= 0) {
                                                $totalDaysToCount = 1;
                                            }

                                            if ($task['removal']['rental']['charge_basis'] == 'daily') {
                                                $thisRentalRate = $task['removal']['rental']['daily_rental'] * $totalDaysToCount;
                                            } else {
                                                if ($totalDaysToCount > 2) {
                                                    $getTotalWeekOrDaysCount = round($totalDaysToCount / 7);
                                                    $getTotalWeekOrDaysCount = ($getTotalWeekOrDaysCount <= 0 ? 1 : $getTotalWeekOrDaysCount);

                                                    $thisRentalRate = ($task['removal']['rental']['monthly_rental'] / 4) * $getTotalWeekOrDaysCount;
                                                } else {
                                                    $thisRentalRate = ($task['removal']['rental']['daily_rental']) * $getTotalWeekOrDaysCount;
                                                }
                                            }

                                            $theTax = \App\Models\Tax::find($task['removal']['rental']['tax']);

                                            if ($theTax) {
                                                $finalTotalForTax = ($thisRentalRate * $theTax->rate) / 100;
                                            }

                                            $newRentalToBeAdd = 0;

                                            $pendingToAddNRental = InvoiceItem::whereHas('task.removal.rental', function ($inB) use ($task) {
                                                $inB->where('id', $task['removal']['rental']['id']);
                                            })->whereHas('task', function ($inB) {
                                                $inB->where('job_type', 0);
                                            })
                                            ->exists();

                                            if (!$pendingToAddNRental) {
                                                $newRentalToBeAdd = $task['removal']['rental']['new_rental'];
                                            }
                                            
                                            InvoiceItem::create([
                                                'invoice_id' => $mainInvoice->id,
                                                'should_mail' => $customer->email_invoice,
                                                'from' => $initialDate,
                                                'to' => $myToDate,
                                                'task_id' => Task::select('id')->where('job_type', 0)->whereHas('removal', function ($builder) use ($task) {
                                                    $builder->where('rental_id', $task['removal']['rental']['id']);
                                                })->first()->id ?? null,
                                                'amount' => $thisRentalRate + $newRentalToBeAdd,
                                                'tax_amount' => $finalTotalForTax,
                                                'total_amount' => $thisRentalRate + $finalTotalForTax,
                                                'task_date' => $task['task_date'],
                                                'type' => 0
                                            ]);
                                        }

                                        InvoiceItem::create([
                                            'invoice_id' => $mainInvoice->id,
                                            'should_mail' => $customer->email_invoice,
                                            'from' => $task['legacy_code'],
                                            'to' => $task['job_completed_at'],
                                            'task_id' => $task['id'],
                                            'amount' => isset($task['removal']) ? $task['removal']['total_removal'] : 0,
                                            'tax_amount' => isset($task['removal']) ? $task['removal']['total_tax'] : 0,
                                            'total_amount' => isset($task['removal']) ? $task['removal']['total'] : 0,
                                            'task_date' => $task['task_date'],
                                            'type' => $task['service_type'] == 1 ? 2 :(isset($task['removal']) && ($task['removal']['removal_type'] == 4) ? 0 : 1)
                                        ]);
                                    }
                                }
                            }
                        }
                }
            }

            \DB::commit();
            return response()->json(['status' => true, 'message' => 'Invoice generated successfully']);
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Invoice run error: ' . $e->getMessage() . ' get line :' . $e->getLine());

            return response()->json(['status' => false, 'message' => Helper::$errorMessage]);
        }

    }

    public function destroy($id)
    {
        $invoice = Invoice::find(decrypt($id));
        InvoiceItem::where('invoice_id', $invoice->id)->delete();
        $invoice->delete();
        
        return redirect()->route('invoices.index')->with('success','Invoice deleted successfully');
    }

    public function show($id) {
        $page_title = 'Invoice Show';
        $invoice = Invoice::find(decrypt($id));
    
        return view('invoices.show', compact('invoice', 'page_title'));
    }

    public function generatePdf($id) {
        $invoice = Invoice::find(decrypt($id));
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.pdf', ['invoice' => $invoice]);
            
        return $pdf->stream('order-report.pdf');
    }

    public static function getDates($startingDate, $year, $month, $frequency)
    {
        $monthNumber = Carbon::createFromFormat('m', $month)->format('m');

        $tempStartDate = Carbon::createFromDate($year, $monthNumber, $startingDate);
        
        $startDate = $tempStartDate->copy()->subMonth();
        $endDate = $tempStartDate;

        switch (strtolower($frequency)) {
            case 'daily':
                $dates = [];
                foreach ($startDate->toPeriod($endDate) as $date) {
                    $dates[] = $date->toDateString();
                }
                return $dates;
            case 'weekly':
                $weeks = [];
                for ($date = $startDate->copy(); $date->lte($endDate); $date->addWeek()) {
                    $weeks[] = [
                        $date->copy()->startOfWeek()->toDateString(),
                        $date->copy()->endOfWeek()->toDateString()
                    ];
                }
                return $weeks;
            case 'monthly':
                return [
                    $startDate->toDateString(),
                    $endDate->toDateString()
                ];
            default:
                return [];
        }
    }

    public function export(Request $request) {
        $reqData = $request->all();

        $invoices = Invoice::with('items')
        ->when(isset($reqData['customer']), function ($builder) use ($reqData) {
            return $builder->where('customer_id', $reqData['customer']);
        })
        ->when(isset($reqData['month']), function ($builder) use ($reqData) {
            return $builder->whereHas('items', function ($innerBuilder) use ($reqData) {
                return $innerBuilder->where(\DB::raw("DATE_FORMAT(task_date, '%m')"), $reqData['month']);
            });
        })
        ->when(isset($reqData['year']), function ($builder) use ($reqData) {
            return $builder->whereHas('items', function ($innerBuilder) use ($reqData) {
                return $innerBuilder->where(\DB::raw("DATE_FORMAT(task_date, '%Y')"), $reqData['year']);
            });
        })
        ->when(isset($reqData['billcycle']), function ($builder) use ($reqData) {
            return $builder->where('billing_cycle', $reqData['billcycle']);
        });

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\InvoiceExport($invoices->orderBy('id', 'DESC')->get()),'invoices.xlsx');
    }

    public function invoiceGeneration(Request $request) {
        if ($request->ajax()) {
            $requestData = $request->all();
            $billingCycle = explode(',', $request->cycle);
            $accountType = explode(',', $request->accounttype);
            $invoiceGenerateFrom = '2025-03-15 00:00:00';

            $allData = [];

            $customers = User::customer()->whereIn('billing_cycle', $billingCycle)
            ->when(in_array('long', $accountType), function ($thisBuilder) {
                return $thisBuilder->where('account_type', 0);
            })
            ->when(in_array('short', $accountType), function ($thisBuilder) {
                return $thisBuilder->where('account_type', 1);
            })
            ->where('on_hold', 0)
            ->where('status', 1)
            ->whereHas('tsks', function ($builder) use ($invoiceGenerateFrom) {
                return $builder->where('task_date', '>=', $invoiceGenerateFrom)
                ->where('status', 5)
                ->whereDoesntHave('invoiceitem');
            })
            ->get();
            foreach ($customers as $customer) {

                $dates = [$invoiceGenerateFrom, date('Y-m-d 23:59:59', strtotime($request->fromdate))];

                if (!empty($dates)) {

                    // from now will be generate the wohle invoice
                        $tasks = Task::with('removal.rental')
                        ->where('task_date', '>=', $invoiceGenerateFrom)
                        ->when(!empty(explode(',', $requestData['type'])), function ($thisBuilder) {
                            $thisBuilder->where(function ($inrBuilder) {
                                $theData = explode(',', request('type'));

                                if ($theData) {
                                    foreach ($theData as $k => $value) {
                                        if ($k) {
                                            if ($value == 'rentals') {
                                                $inrBuilder->orWhere('job_type', 0);
                                            } else if ($value == 'removals') {
                                                $inrBuilder->orWhere('job_type', '!=', 0);
                                            } else if ($value == 'services') {
                                                $inrBuilder->orWhere(function ($inInBuilder) {
                                                    $inInBuilder->where('service_type', 1)->where('job_type', 1);
                                                });
                                            }
                                        } else {
                                            if ($value == 'rentals') {
                                                $inrBuilder->where('job_type', 0);
                                            } else if ($value == 'removals') {
                                                $inrBuilder->where('job_type', '!=', 0);
                                            } else if ($value == 'services') {
                                                $inrBuilder->where(function ($inInBuilder) {
                                                    $inInBuilder->where('service_type', 1)->where('job_type', 1);
                                                });
                                            }
                                        }
                                    }
                                }
                            });
                        })
                        ->whereDoesntHave('invoiceitem')
                        ->where('customer_id', $customer->id)->where(\DB::raw("DATE_FORMAT(task_date, '%Y-%m-%d')"), '>=', date('Y-m-d', strtotime($dates[0])))
                        ->where(\DB::raw("DATE_FORMAT(task_date, '%Y-%m-%d')"), '<=', date('Y-m-d', strtotime($dates[1])))
                        ->where('status', 5);

                        $initialDate = $tasks->clone()->orderBy('task_date', 'ASC')->first()->task_date ?? null;
                        $tasks = $tasks->get()->toArray();

                        if (!empty($tasks) && count($tasks[0]) == 1 && $tasks[0]['removal']['removal_type'] == 4) {
                            continue;
                        }

                        if (isset($dates[0]) && isset($dates[1])) {

                            $tasksToBeConsidered = array_column($tasks, 'id');

                            if (!empty($tasks) && InvoiceItem::whereIn('task_id', $tasksToBeConsidered)->doesntExist()) {

                                foreach ($tasks as $task) {
                                    // Calculation charge basis wise
                                    if (isset($task['removal']['rental']['id'])) {
                                        $myFromDate = Carbon::parse($initialDate)->format('Y-m-d 00:00:00');
                                        $myToDate = Carbon::parse($dates[1])->format('Y-m-d H:i:s');

                                        if (Carbon::parse($task['removal']['rental']['to_date'])->startOfDay()->lt(Carbon::parse($dates[1])->startOfDay())) {
                                            $myToDate = Carbon::parse($task['removal']['rental']['to_date'])->format('Y-m-d 23:59:59');
                                        }

                                        $totalDaysToCount = Carbon::parse($myFromDate)->diffInDays(Carbon::parse($myToDate));
                                        $getTotalWeekOrDaysCount = 1;
                                        $thisRentalRate = 0;

                                        if ($totalDaysToCount <= 0) {
                                            $totalDaysToCount = 1;
                                        }
                                        if ($task['removal']['rental']['charge_basis'] == 'daily') {
                                            $thisRentalRate = $task['removal']['rental']['daily_rental'] * $totalDaysToCount;
                                        } else {
                                            if ($totalDaysToCount > 2) {
                                                $getTotalWeekOrDaysCount = round($totalDaysToCount / 7);
                                                $getTotalWeekOrDaysCount = ($getTotalWeekOrDaysCount <= 0 ? 1 : $getTotalWeekOrDaysCount);

                                                $thisRentalRate = ($task['removal']['rental']['monthly_rental'] / 4) * $getTotalWeekOrDaysCount;
                                            } else {
                                                $thisRentalRate = ($task['removal']['rental']['daily_rental']) * $getTotalWeekOrDaysCount;
                                            }
                                        }

                                        $theTax = \App\Models\Tax::find($task['removal']['rental']['tax']);

                                        if ($theTax) {
                                            $finalTotalForTax = ($thisRentalRate * $theTax->rate) / 100;
                                            $thisRentalRate += $finalTotalForTax;
                                        }
                                    }
                                    // Calculation charge basis wise

                                    if (isset($task['removal']['rental']['id']) && $task['removal']['removal_type'] == 4) {
                                        $totalToBeCons = $thisRentalRate;
                                    } else {
                                        $totalToBeCons = isset($task['removal']) ? $task['removal']['total'] : 0;
                                    }

                                    if (isset($allData[$customer->id])) {
                                        $allData[$customer->id]['total'] += $totalToBeCons;
                                    } else {
                                        $allData[$customer->id]['checkbox'] = "<input type='checkbox' class='check-user' value='$customer->id' />";
                                        $allData[$customer->id]['name'] = $customer->name;
                                        $allData[$customer->id]['total'] = $totalToBeCons;
                                    }
                                }

                            }
                        }     
                    // from now will be generate the wohle invoice

                }
            }

            $html = '';

            if (!empty($allData)) {
                $thisTotal = 0;
                foreach ($allData as $row) {
                    $html .= '<tr> 
                        <td> '. $row['checkbox'] .' </td>
                        <td> '. $row['name'] .' </td>
                        <td> '. number_format($row['total'], 2) .' </td>
                    </tr>';

                    $thisTotal += $row['total'];
                }

                $html .= '<tr> 
                    <td colspan="2"> <strong> Total </strong> </td>
                    <td> <strong> ' . number_format($thisTotal, 2) . ' </strong> </td>
                </tr>';
            }

            return response()->json(['status' => true, 'html' => $html]);
        }

        $page_title = 'Invoice Run';
        return view('invoices.list', compact('page_title'));
    }
}
