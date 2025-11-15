<?php

namespace App\Services;

use App\Models\SigningGroup;
use App\Models\UserGroup;
use App\Models\User;
use App\Models\Brand;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GroupService
{
    // ============================================
    // SIGNING GROUPS
    // ============================================

    public function getSigningGroups(int $accountId, array $options = []): Collection
    {
        $query = SigningGroup::forAccount($accountId);

        if (isset($options['group_type'])) {
            $query->ofType($options['group_type']);
        }

        if (isset($options['include_users']) && $options['include_users'] === 'true') {
            $query->with('users');
        }

        return $query->get();
    }

    public function getSigningGroup(int $accountId, string $signingGroupId): ?SigningGroup
    {
        return SigningGroup::forAccount($accountId)
            ->where('signing_group_id', $signingGroupId)
            ->with('users')
            ->first();
    }

    public function createSigningGroup(int $accountId, array $data, int $userId): SigningGroup
    {
        return SigningGroup::create([
            'account_id' => $accountId,
            'group_name' => $data['group_name'],
            'group_email' => $data['group_email'] ?? null,
            'group_type' => $data['group_type'] ?? SigningGroup::TYPE_PUBLIC,
            'created_by' => $userId,
            'modified_by' => $userId,
        ]);
    }

    public function updateSigningGroups(int $accountId, array $groups, int $userId): Collection
    {
        $updated = collect();

        DB::beginTransaction();
        try {
            foreach ($groups as $groupData) {
                if (isset($groupData['signing_group_id'])) {
                    $group = $this->getSigningGroup($accountId, $groupData['signing_group_id']);
                    if ($group) {
                        $group->update([
                            'group_name' => $groupData['group_name'] ?? $group->group_name,
                            'group_email' => $groupData['group_email'] ?? $group->group_email,
                            'group_type' => $groupData['group_type'] ?? $group->group_type,
                            'modified_by' => $userId,
                        ]);
                        $updated->push($group->fresh('users'));
                    }
                }
            }
            DB::commit();
            return $updated;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteSigningGroups(int $accountId, array $signingGroupIds): int
    {
        return SigningGroup::forAccount($accountId)
            ->whereIn('signing_group_id', $signingGroupIds)
            ->delete();
    }

    public function getSigningGroupUsers(int $accountId, string $signingGroupId): Collection
    {
        $group = $this->getSigningGroup($accountId, $signingGroupId);
        return $group ? $group->users : collect();
    }

    public function addSigningGroupUsers(int $accountId, string $signingGroupId, array $users): Collection
    {
        $group = $this->getSigningGroup($accountId, $signingGroupId);
        if (!$group) {
            throw new \Exception("Signing group not found");
        }

        DB::beginTransaction();
        try {
            foreach ($users as $userData) {
                $user = User::where('email', $userData['email'])->first();
                if ($user) {
                    $group->users()->syncWithoutDetaching([
                        $user->id => [
                            'email' => $userData['email'],
                            'user_name' => $userData['user_name'] ?? $user->name,
                        ]
                    ]);
                }
            }
            DB::commit();
            return $group->fresh('users')->users;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteSigningGroupUsers(int $accountId, string $signingGroupId, array $userIds): int
    {
        $group = $this->getSigningGroup($accountId, $signingGroupId);
        if (!$group) {
            return 0;
        }

        $users = User::whereIn('email', $userIds)->orWhereIn('id', $userIds)->pluck('id');
        return $group->users()->detach($users);
    }

    // ============================================
    // USER GROUPS
    // ============================================

    public function getUserGroups(int $accountId): Collection
    {
        return UserGroup::forAccount($accountId)
            ->with(['users', 'permissionProfile', 'brands'])
            ->get();
    }

    public function getUserGroup(int $accountId, string $groupId): ?UserGroup
    {
        return UserGroup::forAccount($accountId)
            ->where('group_id', $groupId)
            ->with(['users', 'permissionProfile', 'brands'])
            ->first();
    }

    public function createUserGroups(int $accountId, array $groups, int $userId): Collection
    {
        $created = collect();

        DB::beginTransaction();
        try {
            foreach ($groups as $groupData) {
                $group = UserGroup::create([
                    'account_id' => $accountId,
                    'group_name' => $groupData['group_name'],
                    'group_type' => $groupData['group_type'] ?? UserGroup::TYPE_CUSTOM_GROUP,
                    'permission_profile_id' => $groupData['permission_profile_id'] ?? null,
                    'created_by' => $userId,
                    'modified_by' => $userId,
                ]);
                $created->push($group);
            }
            DB::commit();
            return $created;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateUserGroups(int $accountId, array $groups, int $userId): Collection
    {
        $updated = collect();

        DB::beginTransaction();
        try {
            foreach ($groups as $groupData) {
                if (isset($groupData['group_id'])) {
                    $group = $this->getUserGroup($accountId, $groupData['group_id']);
                    if ($group) {
                        $group->update([
                            'group_name' => $groupData['group_name'] ?? $group->group_name,
                            'group_type' => $groupData['group_type'] ?? $group->group_type,
                            'permission_profile_id' => $groupData['permission_profile_id'] ?? $group->permission_profile_id,
                            'modified_by' => $userId,
                        ]);
                        $updated->push($group->fresh(['users', 'permissionProfile', 'brands']));
                    }
                }
            }
            DB::commit();
            return $updated;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteUserGroups(int $accountId, array $groupIds): int
    {
        return UserGroup::forAccount($accountId)
            ->whereIn('group_id', $groupIds)
            ->delete();
    }

    public function getUserGroupUsers(int $accountId, string $groupId): Collection
    {
        $group = $this->getUserGroup($accountId, $groupId);
        return $group ? $group->users : collect();
    }

    public function addUserGroupUsers(int $accountId, string $groupId, array $userIds): Collection
    {
        $group = $this->getUserGroup($accountId, $groupId);
        if (!$group) {
            throw new \Exception("User group not found");
        }

        $users = User::whereIn('id', $userIds)->orWhereIn('email', $userIds)->pluck('id');
        $group->users()->syncWithoutDetaching($users);

        return $group->fresh('users')->users;
    }

    public function deleteUserGroupUsers(int $accountId, string $groupId, array $userIds): int
    {
        $group = $this->getUserGroup($accountId, $groupId);
        if (!$group) {
            return 0;
        }

        $users = User::whereIn('id', $userIds)->orWhereIn('email', $userIds)->pluck('id');
        return $group->users()->detach($users);
    }

    public function getUserGroupBrands(int $accountId, string $groupId): Collection
    {
        $group = $this->getUserGroup($accountId, $groupId);
        return $group ? $group->brands : collect();
    }

    public function addUserGroupBrands(int $accountId, string $groupId, array $brandIds): Collection
    {
        $group = $this->getUserGroup($accountId, $groupId);
        if (!$group) {
            throw new \Exception("User group not found");
        }

        $brands = Brand::whereIn('brand_id', $brandIds)->pluck('id');
        $group->brands()->syncWithoutDetaching($brands);

        return $group->fresh('brands')->brands;
    }

    public function deleteUserGroupBrands(int $accountId, string $groupId): int
    {
        $group = $this->getUserGroup($accountId, $groupId);
        if (!$group) {
            return 0;
        }

        return $group->brands()->detach();
    }
}
