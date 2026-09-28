<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\WhatsApp\WhatsAppContactService;
use App\Services\WhatsApp\WhatsAppDestinationResolver;
use App\Models\WhatsAppContact;
use PHPUnit\Framework\TestCase;

class WhatsAppPhoneNormalizationTest extends TestCase
{
    public function test_user_clean_phone_removes_member_suffix(): void
    {
        $user = new User(['phone' => '3312345678-02']);

        $this->assertSame('3312345678', $user->clean_phone);
    }

    public function test_contact_canonical_phone_removes_member_suffix_before_adding_country_code(): void
    {
        $service = new WhatsAppContactService;

        $this->assertSame('523312345678', $service->canonicalPhone('3312345678-02'));
    }

    public function test_destination_resolver_uses_only_the_base_phone_number(): void
    {
        $resolver = new WhatsAppDestinationResolver;

        $this->assertSame(
            ['5213312345678', '523312345678'],
            $resolver->resolve('3312345678-02', '52')
        );
    }

    public function test_console_contact_uses_the_linked_users_clean_phone_and_country_code(): void
    {
        $user = new User(['phone' => '3312345678-02', 'phone_country_code' => '52']);
        $contact = new WhatsAppContact(['phone' => '3312345678-02']);
        $contact->setRelation('user', $user);

        $this->assertSame(
            '523312345678',
            (new WhatsAppContactService)->destinationPhone($contact)
        );
    }
}
