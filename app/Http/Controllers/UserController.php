<?php
namespace App\Http\Controllers;
use App\Helpers\IdGenerator;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->search, fn($q, $s) =>
                $q->where('nama_user', 'like', "%{$s}%")
                  ->orWhere('email',   'like', "%{$s}%")
            )
            ->when($request->role,   fn($q, $v) => $q->where('role',   $v))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->orderBy('role')
            ->orderBy('nama_user')
            ->paginate(10)
            ->withQueryString();
        return view('dashboard.users.index', compact('users'));
    }
    public function create()
    {
        return view('dashboard.users.create');
    }
    public function store(Request $request)
    {
        abort_if(!auth()->user()->punyaAkses('users', 'tambah'), 403, 'Anda tidak memiliki akses untuk menambah user.');
        $data = $request->validate([
            'nama_user'     => 'required|string|max:100',
            'jenis_kelamin' => 'required|in:L,P',
            'alamat'        => 'required|string|max:255',
            'nohp'          => 'required|string|max:20|unique:users,nohp',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|string|min:6',
            'role'          => 'required|in:admin,operator,inventaris',
        ]);
        User::create([
            'id_user'       => IdGenerator::next(User::class, 'id_user', 'USR-'),
            'nama_user'     => $data['nama_user'],
            'jenis_kelamin' => $data['jenis_kelamin'],
            'alamat'        => $data['alamat'],
            'nohp'          => $data['nohp'],
            'email'         => $data['email'],
            'password'      => bcrypt($data['password']),
            'role'          => $data['role'],
            'status'        => 'active',
        ]);
        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }
    public function edit(string $id)
    {
        return view('dashboard.users.edit', ['user' => User::findOrFail($id)]);
    }
    public function update(Request $request, string $id)
    {
        abort_if(!auth()->user()->punyaAkses('users', 'ubah'), 403, 'Anda tidak memiliki akses untuk mengubah user.');
        $user = User::findOrFail($id);
        $data = $request->validate([
            'nama_user'     => 'required|string|max:100',
            'jenis_kelamin' => 'required|in:L,P',
            'alamat'        => 'required|string|max:255',
            'nohp'          => 'required|string|max:20|unique:users,nohp,' . $id . ',id_user',
            'email'         => 'required|email|unique:users,email,' . $id . ',id_user',
            'password'      => 'nullable|string|min:6',
            'role'          => 'required|in:admin,operator,inventaris',
        ]);
        $update = [
            'nama_user'     => $data['nama_user'],
            'jenis_kelamin' => $data['jenis_kelamin'],
            'alamat'        => $data['alamat'],
            'nohp'          => $data['nohp'],
            'email'         => $data['email'],
            'role'          => $data['role'],
        ];
        if (!empty($data['password'])) {
            $update['password'] = bcrypt($data['password']);
        }
        $user->update($update);
        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil diperbarui.');
    }
    public function destroy(string $id)
    {
        abort_if(!auth()->user()->punyaAkses('users', 'hapus'), 403, 'Anda tidak memiliki akses untuk mengaktifkan/menonaktifkan user.');
        $user = User::findOrFail($id);
        if ($user->id_user === auth()->user()->id_user) {
            return back()->with('error', 'Tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $newStatus = $user->isActive() ? 'inactive' : 'active';

        if ($newStatus === 'inactive' && $user->role === 'admin') {
            $jumlahAdminAktif = User::where('role', 'admin')->where('status', 'active')->count();
            if ($jumlahAdminAktif <= 1) {
                return back()->with('error', 'Tidak dapat menonaktifkan admin ini karena ini satu-satunya akun admin yang aktif.');
            }
        }

        $user->update(['status' => $newStatus]);
        $label = $newStatus === 'active' ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "User berhasil {$label}.");
    }
}
