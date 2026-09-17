<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\dashboard\Dashboard;

class DashboardController extends Controller
{
    public function dataCustomers()
    {
        $data = Dashboard::getCustomers();
        
        $hasil = [];
        $status = false;

        if (count($data) > 0) {
            $status = true;
            foreach ($data as $key => $row) {
                $hasil[$key]['value'] = $row->id_customers;
                $hasil[$key]['text'] = $row->nm_customers;
            }
        }

        return response()->json([
            'status' => $status,
            'data' => $hasil
        ]);
    }

    public function dataArReport(Request $request)
    {
        $dfrom = date("Y-m-d", strtotime($request->input('dfrom')));
        $dto = date("Y-m-d", strtotime($request->input('dto')));
        $id_customers = $request->input('id_customers');
        $all_data_ar = $request->input('all_data_ar');

        $data = Dashboard::getArReport($dfrom, $dto, $id_customers, $all_data_ar);

        $status = false;
        if (count($data) > 0) {
            $status = true;
        }

        return response()->json([
            'status' => $status,
            'data' => $data
        ]);
    }

    public function dataTeknisiPp()
    {
        $data = Dashboard::getTeknisiPP();
        
        $status = false;
        $hasil = [];

        if (count($data) > 0) {
            $status = true;
            foreach ($data as $key => $row) {
                $hasil[$key]['nm_karyawan'] = $row->nm_karyawan;
                $hasil[$key]['tgl'] = date('d-M-Y');
            }
        }

        return response()->json([
            'status' => $status,
            'data' => $hasil
        ]);
    }

    public function dataTeknisiPl()
    {
        $data = Dashboard::getTeknisiPL();
        
        $status = false;
        $hasil = [];

        if (count($data) > 0) {
            $status = true;
            foreach ($data as $key => $row) {
                $hasil[$key]['nm_karyawan'] = $row->nm_karyawan;
                $hasil[$key]['tgl'] = date('d-M-Y');
            }
        }

        return response()->json([
            'status' => $status,
            'data' => $hasil
        ]);
    }

    public function dataJadwalLkt()
    {
        $data = Dashboard::getJadwalLkt();
        
        $status = false;
        if (count($data) > 0) {
            $status = true;
        }

        return response()->json([
            'status' => $status,
            'data' => $data
        ]);
    }

    public function dataTotalRequestPendingProgres()
    {
        $data = Dashboard::getTotalRequestPendingProgres();

        $total_request = 0;
        $total_pending = 0;
        $total_progres = 0;

        if (count($data) > 0) {
            $row = $data[0];
            $total_request = $row->total_request;
            $total_pending = $row->total_pending;
            $total_progres = $row->total_progres;
        }

        return response()->json([
            'total_request' => $total_request,
            'total_pending' => $total_pending,
            'total_progres' => $total_progres
        ]);
    }

    public function agingAr()
    {
        $data = Dashboard::getAgingAr();

        $hasil = [];

        $nm_customers_sebelum = '';
        $vcurrency_sebelum = '';
        $isian = [
            'nm_customers' => '',
            'vcurrency' => '',
            'hiji' => 0,
            'dua' => 0,
            'tilu' => 0,
            'opat' => 0
        ];

        foreach ($data as $row) {
            $nm_customers = $row->nm_customers;
            $date_payment = $row->date_payment;
            $v_amount = $row->v_amount;
            $vcurrency = $row->vcurrency;

            $date_payment_str = date("d-M-Y", strtotime($date_payment));

            $awal  = strtotime($date_payment_str);
            $akhir = time();
            $diff  = $akhir - $awal;
            $hari = floor($diff / (60 * 60 * 24));

            if (($nm_customers != $nm_customers_sebelum) || (($nm_customers == $nm_customers_sebelum) && ($vcurrency != $vcurrency_sebelum))) {
                $hasil[] = $isian;
                $isian = [
                    'nm_customers' => '',
                    'vcurrency' => '',
                    'hiji' => 0,
                    'dua' => 0,
                    'tilu' => 0,
                    'opat' => 0
                ];
            }

            $isian['nm_customers'] = $nm_customers;
            $isian['vcurrency'] = $vcurrency;

            if ($hari >= 0 && $hari <= 3) {
                $isian['hiji'] += $v_amount;
            } elseif ($hari >= 4 && $hari <= 5) {
                $isian['dua'] += $v_amount;
            } elseif ($hari >= 6 && $hari <= 10) {
                $isian['tilu'] += $v_amount;
            } elseif ($hari >= 11) {
                $isian['opat'] += $v_amount;
            }

            $nm_customers_sebelum = $nm_customers;
            $vcurrency_sebelum = $vcurrency;
        }
        
        $hasil[] = $isian;

        // Removing the initial empty structure (like CI unset($hasil[0]))
        if (isset($hasil[0]) && $hasil[0]['nm_customers'] == '') {
            unset($hasil[0]);
        }

        // Re-index array
        $hasil = array_values($hasil);

        return response()->json($hasil);
    }

    public function dataArReport2(Request $request)
    {
        $tanggal = date("Y-m-d", strtotime($request->input('tanggal')));
        $id_customers = $request->input('id_customers');
        $mata_uang = $request->input('mata_uang');

        $data = Dashboard::getArReport2($tanggal, $id_customers, $mata_uang);

        $status = false;
        if (count($data) > 0) {
            $status = true;
        }

        return response()->json([
            'status' => $status,
            'data' => $data
        ]);
    }

    public function top20PriceCheckProducts($month = null, $year = null)
    {
        if ($month === null || $year === null) {
            $current_month = date('m');
            $current_year = date('Y');
        } else {
            $current_month = $month;
            $current_year = $year;
        }

        $start_date = "$current_year-$current_month-01";
        $end_date = date('Y-m-t', strtotime($start_date));

        $data = Dashboard::getTop20PriceCheckProducts($start_date, $end_date);

        return response()->json($data);
    }

    public function topProductsQuotations($month, $year)
    {
        $start_date = "$year-$month-01";
        $end_date = date('Y-m-t', strtotime($start_date));

        $data = Dashboard::getTopProductsQuotations($start_date, $end_date);

        $status = false;
        if (count($data) > 0) {
            $status = true;
        }

        return response()->json([
            'status' => $status,
            'data' => $data
        ]);
    }

    public function quotationAndSoStatistics($month, $year)
    {
        $start_date = "$year-$month-01";
        $end_date = date("Y-m-t", strtotime($start_date));

        $data = Dashboard::getQuotationAndSoStatistics($start_date, $end_date);

        if (count($data) > 0) {
            $row = $data[0];
            $quotations = $row->quotations;
            $total_so = $row->total_so;
            $total_cancelled = $row->total_cancelled;
            $total_quotations = $row->total_q;

            $success_rate = $total_quotations > 0
                ? ($total_so / $total_quotations) * 100
                : 0;

            $result = [
                'quotations' => $quotations,
                'total_quotations' => $total_quotations,
                'total_so' => $total_so,
                'total_cancelled' => $total_cancelled,
                'success_rate' => number_format($success_rate, 2)
            ];
        } else {
            $result = [
                'quotations' => 0,
                'total_quotations' => 0,
                'total_so' => 0,
                'total_cancelled' => 0,
                'success_rate' => 0
            ];
        }

        return response()->json($result);
    }

    public function dataDoOutstanding(Request $request)
    {
        $tanggal = date("Y-m-d", strtotime($request->input('tanggal')));

        $data = Dashboard::getDoOutstanding($tanggal);

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function dataRequestCsr()
    {
        $data = \Illuminate\Support\Facades\DB::table('tb_afs_csr as a')
            ->select('a.*', 'b.nm_karyawan', 'c.nm_customers', 'd.code_product')
            ->join('m_karyawan as b', 'a.id_karyawan', '=', 'b.id_karyawan')
            ->join('m_customers as c', 'a.id_customers', '=', 'c.id_customers')
            ->join('m_product as d', 'a.id_product', '=', 'd.id_product')
            ->where('a.csr_status', 'DRAFT')
            ->where('a.f_cancel', 0)
            ->orderBy('a.csr_code', 'asc')
            ->get();
        return response()->json($data);
    }

    public function dataPendingCst()
    {
        $data = \Illuminate\Support\Facades\DB::table('tb_afs_cst as a')
            ->select('a.*', 'b.csr_date', 'b.f_cancel', 'c.nm_customers', 'd.code_product')
            ->join('tb_afs_csr as b', 'a.id_afs_csr', '=', 'b.id_afs_csr')
            ->join('m_customers as c', 'b.id_customers', '=', 'c.id_customers')
            ->join('m_product as d', 'b.id_product', '=', 'd.id_product')
            
            ->where('a.status', 'OUTSTANDING')
            ->where('b.f_cancel', 0)
            ->orderBy('a.cst_code', 'desc')
            ->get();
        return response()->json($data);
    }

    public function dataOngoingCst()
    {
        $data = \Illuminate\Support\Facades\DB::table('tb_afs_cst as a')
            ->select(
                'a.id_afs_cst', 'a.cst_code', 'a.status', 'a.cst_date', 
                'b.id_afs_csr', 'b.f_cancel', 'b.csr_date',
                'd.nm_customers', 
                'e.code_product',
                \Illuminate\Support\Facades\DB::raw('MAX(f.lkt_sub_code) as max_lkt_sub_code'),
                \Illuminate\Support\Facades\DB::raw('MAX(f.actual_starting_date) as max_actual_starting_date'),
                \Illuminate\Support\Facades\DB::raw('MAX(g.starting_date) as starting_date'),
                \Illuminate\Support\Facades\DB::raw('MAX(g.lkt_cancel_date) as lkt_cancel_date'),
                \Illuminate\Support\Facades\DB::raw('MAX(g.lkt_done_date) as lkt_done_date'),
                \Illuminate\Support\Facades\DB::raw('MAX(g.flag_done) as flag_done'),
                \Illuminate\Support\Facades\DB::raw('MAX(g.f_cancel) as lkt_f_cancel')
            )
            ->join('tb_afs_csr as b', 'a.id_afs_csr', '=', 'b.id_afs_csr')
            ->join('m_customers as d', 'b.id_customers', '=', 'd.id_customers')
            ->join('m_product as e', 'b.id_product', '=', 'e.id_product')
            ->leftJoin('tb_afs_lkt as g', 'a.id_afs_cst', '=', 'g.id_afs_cst')
            ->leftJoin('tb_afs_realisasi as f', 'f.id_afs_lkt', '=', 'g.id_afs_lkt')
            ->where('a.status', 'ON PROGRESS')
            ->where('b.f_cancel', 0)
            ->groupBy('a.id_afs_cst', 'a.cst_code', 'a.status', 'a.cst_date', 'b.id_afs_csr', 'b.f_cancel', 'b.csr_date', 'd.nm_customers', 'e.code_product')
            ->orderBy('a.cst_code', 'desc')
            ->get();
        return response()->json($data);
    }
}