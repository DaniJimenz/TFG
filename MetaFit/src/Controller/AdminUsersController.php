<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\TrainingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/admin/users', name: 'admin_users_')]
#[IsGranted('ROLE_ADMIN')]
class AdminUsersController extends AbstractController
{
    /**
     * Listar todos los usuarios
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(UserRepository $userRepository, Request $request): Response
    {
        $page = max(1, (int)$request->query->get('page', 1));
        $perPage = 20;
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'created_at');
        $direction = $request->query->get('direction', 'DESC');

        // Construir query
        $query = $userRepository->createQueryBuilder('u');

        if ($search) {
            $query->where('u.email LIKE :search OR u.name LIKE :search OR u.lastname LIKE :search')
                ->setParameter('search', "%{$search}%");
        }

        $countQuery = clone $query;
        $total = (int) $countQuery->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();

        $users = $query
            ->orderBy("u.{$sort}", $direction)
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
            ],
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * Ver detalle de usuario
     */
    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin/users/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Editar usuario
     */
    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(User $user, Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            if (!$this->isCsrfTokenValid('edit_user_' . $user->getId(), $data['_token'] ?? '')) {
                $this->addFlash('error', 'Token de seguridad inválido.');
                return $this->redirectToRoute('admin_users_edit', ['id' => $user->getId()]);
            }

            $constraints = new Assert\Collection([
                'email' => new Assert\Required([new Assert\NotBlank(), new Assert\Email()]),
                'name' => new Assert\Optional([new Assert\Type('string')]),
                'lastname' => new Assert\Optional([new Assert\Type('string')]),
                'age' => new Assert\Optional([new Assert\Type('numeric')]),
                'height' => new Assert\Optional([new Assert\Type('numeric')]),
                'actual_weight' => new Assert\Optional([new Assert\Type('numeric')]),
                'points_xp' => new Assert\Optional([new Assert\Type('numeric')]),
                'level' => new Assert\Optional([new Assert\Type('numeric')]),
            ]);
            $constraints->allowExtraFields = true;
            $constraints->allowMissingFields = true;

            $violations = $validator->validate($data, $constraints);
            if (count($violations) > 0) {
                $this->addFlash('error', 'Revisa los datos introducidos. Formato inválido.');
                return $this->redirectToRoute('admin_users_edit', ['id' => $user->getId()]);
            }

            // Verificar que el email no exista ya en OTRO usuario
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                $this->addFlash('error', 'Este email ya está registrado por otro usuario en la plataforma.');
                return $this->redirectToRoute('admin_users_edit', ['id' => $user->getId()]);
            }

            $user->setEmail($data['email']);
            $user->setName($data['name']);
            $user->setLastname($data['lastname']);
            $user->setAge(isset($data['age']) && $data['age'] !== '' ? (int)$data['age'] : $user->getAge());
            $user->setHeight(isset($data['height']) && $data['height'] !== '' ? (float)$data['height'] : $user->getHeight());
            $user->setGender(!empty($data['gender']) ? $data['gender'] : $user->getGender());
            $user->setActualWeight(isset($data['actual_weight']) && $data['actual_weight'] !== '' ? (float)$data['actual_weight'] : $user->getActualWeight());
            $user->setActivityLevel(!empty($data['activity_level']) ? $data['activity_level'] : $user->getActivityLevel());
            $user->setPointsXp(isset($data['points_xp']) && $data['points_xp'] !== '' ? (int)$data['points_xp'] : $user->getPointsXp());
            $user->setLevel(isset($data['level']) && $data['level'] !== '' ? (int)$data['level'] : $user->getLevel());
            
            if (isset($data['rol'])) {
                $user->setRol($data['rol']);
            }

            $user->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash('success', '¡Usuario actualizado exitosamente!');
            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Eliminar usuario
     */
    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(User $user, EntityManagerInterface $entityManager, Request $request): Response
    {
        // Verificar token CSRF
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $user->setDeletedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Usuario marcado como eliminado.');
        } else {
            $this->addFlash('error', 'Token CSRF inválido.');
        }

        return $this->redirectToRoute('admin_users_index');
    }

    /**
     * Desactivar/Reactivar usuario
     */
    #[Route('/{id}/toggle-status', name: 'toggle_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleStatus(User $user, EntityManagerInterface $entityManager, Request $request): Response
    {
        // Verificar token CSRF
        if ($this->isCsrfTokenValid('toggle' . $user->getId(), $request->request->get('_token'))) {
            if ($user->getDeletedAt()) {
                $user->setDeletedAt(null);
                $this->addFlash('success', 'Usuario reactivado.');
            } else {
                $user->setDeletedAt(new \DateTimeImmutable());
                $this->addFlash('success', 'Usuario desactivado.');
            }
            $entityManager->flush();
        } else {
            $this->addFlash('error', 'Token CSRF inválido.');
        }

        return $this->redirectToRoute('admin_users_show', ['id' => $user->getId()]);
    }

    /**
     * Cambiar rol de usuario
     */
    #[Route('/{id}/change-role', name: 'change_role', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function changeRole(User $user, EntityManagerInterface $entityManager, Request $request): Response
    {
        if ($this->isCsrfTokenValid('role' . $user->getId(), $request->request->get('_token'))) {
            $newRole = $request->request->get('role');
            
            if (in_array($newRole, ['ROLE_USER', 'ROLE_ADMIN'])) {
                $user->setRol($newRole);
                $entityManager->flush();
                $this->addFlash('success', 'Rol actualizado exitosamente.');
            } else {
                $this->addFlash('error', 'Rol inválido.');
            }
        } else {
            $this->addFlash('error', 'Token CSRF inválido.');
        }

        return $this->redirectToRoute('admin_users_show', ['id' => $user->getId()]);
    }

    /**
     * Exportar usuarios a CSV
     */
    #[Route('/export/csv', name: 'export_csv', methods: ['GET'])]
    public function exportCsv(UserRepository $userRepository, TrainingRepository $trainingRepository): Response
    {
        $response = new StreamedResponse(function () use ($userRepository, $trainingRepository) {
            $handle = fopen('php://output', 'w+');
            
            // BOM para que Excel lea los acentos (UTF-8) correctamente
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['ID', 'Email', 'Nombre', 'Apellido', 'Edad', 'Altura', 'Género', 'Actividad', 'XP', 'Nivel', 'Rol', 'Creado', 'Entrenamientos Completados']);

            // La forma ideal sería por bloques (iterables), pero con el stream 
            // evitamos al menos tener un string gigante compilándose
            
            // Pre-cargar todos los conteos de entrenamientos en 1 sola consulta (Adiós N+1)
            $counts = $trainingRepository->createQueryBuilder('t')
                ->select('IDENTITY(t.appUser) as userId, COUNT(t.id) as c')
                ->where('t.completed = true')
                ->groupBy('t.appUser')
                ->getQuery()
                ->getResult();
            $countsMap = array_column($counts, 'c', 'userId');

            $users = $userRepository->findAll();
            foreach ($users as $user) {
                $trainingCount = $countsMap[$user->getId()] ?? 0;
                fputcsv($handle, [
                    $user->getId(),
                    $user->getEmail(),
                    $user->getName() ?? '',
                    $user->getLastname() ?? '',
                    $user->getAge() ?? 0,
                    $user->getHeight() ?? 0,
                    $user->getGender() ?? '',
                    $user->getActivityLevel() ?? '',
                    $user->getPointsXp() ?? 0,
                    $user->getLevel() ?? 0,
                    $user->getRol() ?? 'ROLE_USER',
                    $user->getCreatedAt()->format('Y-m-d H:i:s'),
                    $trainingCount
                ]);
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="users_' . date('Y-m-d_H-i-s') . '.csv"');

        return $response;
    }
}
