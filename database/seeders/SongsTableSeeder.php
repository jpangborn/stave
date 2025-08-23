<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SongsTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('songs')->delete();

        \DB::table('songs')->insert([
            0 => [
                'id' => 1,
                'name' => 'All Creatures Of Our God And King',
                'ccli_number' => '6529800',
                'copyright' => null,
                'lyrics' => '<p>All creatures of our God and King<br>Lift up your voice and with us sing<br>O praise Him! Alleluia!<br>Thou, burning sun with golden beam<br>Thou, silver moon with softer gleam</p><p>O praise Him! O praise Him!<br>Alleluia! Alleluia! Alleluia!</p><p>Let all things their Creator bless<br>And worship Him in humbleness<br>O praise Him! Alleluia!<br>Praise, praise the Father, praise the Son<br>And praise the Spirit, Three-in-One</p><p>All the redeemed washed by His blood<br>Come and rejoice in His great love<br>O praise Him! Alleluia!<br>Christ has defeated every sin<br>Cast all your burdens now on Him</p><p>He shall return in pow’r to reign<br>Heaven and earth will join to say<br>O praise Him! Alleluia!<br>Then who shall fall on bended knee?<br>All creatures of our God and King</p>',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-08-15 23:46:11',
            ],
            1 => [
                'id' => 2,
                'name' => 'Come, O Sinner',
                'ccli_number' => '',
                'copyright' => null,
                'lyrics' => 'VERSE 1
Come, O sinner, come and see
Christ the Lord upon a tree
See the crown of thorns adorn the King
Who labors to breathe in agony
Come, O sinner, come and see
What our God became to set us free

VERSE 2
Come, O sinner, come and mourn
For He calls your sin His own
Do you feel the weight of justice served?
He suffers the wrath that you deserve
Come, O sinner, come and mourn
For He bears the curse for all you’ve done

CHORUS
Oh the wonder of this awesome scene
Where our Savior bleeds
Oh the power of the love of God
Come and stand in awe

VERSE 3
Come, O sinner, come rejoice
Mercy fills this place of scorn
For He dies to save His enemies
That all who draw near may know His peace
Come, O sinner, come rejoice
Through the death of Christ death is destroyed',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            2 => [
                'id' => 3,
                'name' => 'Come Thou Fount of Every Blessing',
                'ccli_number' => '108389',
                'copyright' => null,
                'lyrics' => 'Come Thou fount of every blessing
Tune my heart to sing Thy grace
Streams of mercy never ceasing
Call for songs of loudest praise

Teach me some melodious sonnet
Sung by flaming tongues above
Praise the mount, I\'m fixed upon it
Mount of Thy redeeming love

Here I raise my Ebenezer
Hither by Thy help I\'ve come
And I hope by Thy good pleasure
Safely to arrive at home

Jesus sought me when a stranger
Wandering from the fold of God
Here to rescue me from danger
Interposed His precious blood

Oh, to grace how great a debtor
Daily I\'m constrained to be
Let Thy goodness like a fetter
Bind my wandering heart to Thee

Prone to wander, Lord I feel it
Prone to leave the God I love
Here\'s my heart, oh take and seal it
Seal it for Thy courts above

Oh, that day when freed from sinning
I shall see Thy lovely face
Full-arrayed in blood washed linen
How I\'ll sing Thy sovereign grace

Come my Lord, no longer tarry
Bring Thy promises to pass
For I know Thy pow’r will keep me
Till I\'m home with thee at last',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            3 => [
                'id' => 4,
                'name' => 'To The Highest Place',
                'ccli_number' => '7061554',
                'copyright' => null,
                'lyrics' => 'VERSE
Before the earth was made you were living
And over every heart you’ve been singing
Your very word spoke life into being
You even give the breath we are breathing

PRE-CHORUS
And now, we sing our songs back to you
Oh Lord, we gladly lay down our lives at your feet
We take the breath you gave
And we give it back in praise

CHORUS
\'Cause you are exalted to the highest place
And you have the name above all other names
Lord you are exalted to the highest place
And you have the name above all other names

VERSE 2
Stepping down into a world you made
The uncreated God in a human frame
Creator dwelling with creation
You made yourself of no reputation

PRE-CHORUS
And you made yourself nothing for us
And you gave up your life for the least of men
You entered the gates of death
But rose with the keys in your hands

CHORUS
Now you are exalted to the highest place
And you have the name above all other names
Lord you are exalted to the highest place
And you have the name above all other names

BRIDGE
There is no one like you
There is none besides you
There is no one like you
There is none besides you',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            4 => [
                'id' => 5,
                'name' => 'God Is Good',
                'ccli_number' => '7007922',
                'copyright' => null,
                'lyrics' => 'VERSE 1
Even when it seems the answer\'s no
The promises of God all find their Yes
In Christ who worked the Father\'s will below
That all who run to him would find their rest

VERSE 2
And even when it seems he hides his face
And darkness seems to be our only friend
We look to Christ who suffered in our place
That one day all our suffering would end

CHORUS
God is good, all of the time
All of the time, God is good
God is good, all of the time
All of the time, God is good

VERSE 3
And even when it seems he pays no mind
We have a guarantee of his great love
In Christ who came and left his crown behind
That one day we would reign with him above

BRIDGE
Lord, we believe
But help our unbelief
Lord, we believe
But help our hearts to sing

CHORUS 2
That you are good, all of the time
All of the time, you are good
You are good all, of the time
Your are good
Lord, you are good',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            5 => [
                'id' => 6,
                'name' => 'Build My Life',
                'ccli_number' => '7070345',
                'copyright' => null,
                'lyrics' => 'VERSE 1
Worthy of every song we could ever sing
Worthy of all the praise we could ever bring
Worthy of every breath we could ever breathe
We live for You

VERSE 2
Jesus, the name above every other name
Jesus, the only one who could ever save
Worthy of every breath we could ever breathe
We live for You

CHORUS
Holy, there is no one like You
There is none beside You
Open up my eyes in wonder
Show me who You are
And fill me with Your heart
And lead me in Your love to those around me

BRIDGE
I will build my life upon Your love
It is a firm foundation
I will put my trust in You alone
And I will not be shaken',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            6 => [
                'id' => 7,
                'name' => 'Praise The Lord Ye Heavens',
                'ccli_number' => '7026992',
                'copyright' => null,
                'lyrics' => 'VERSE 1
Praise the Lord Ye heavens adore him
Praise him angels in the height
Sun and moon, rejoice before him
Praise him, all ye stars of light

VERSE 2
Praise the Lord for he hath spoken
Worlds his mighty voice obeyed
Laws which never shall be broken
For their guidance he hath made

CHORUS
All creation join the song of praise
Let every tongue declare His mighty ways
And we will sing of Your goodness and mercy all of our days

VERSE 3
Praise the Lord for he is glorious
Never shall his promise fail
God hath made his saints victorious
Sin and death shall not prevail

BRIDGE
Glory! Glory! All glory to You Lord!

VERSE 4
Praise the God of our salvation
Hosts on high, his power proclaim
Heaven and earth, and all creation
Laud and magnify his Name',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            7 => [
                'id' => 8,
                'name' => 'I Lay It All',
                'ccli_number' => '7096631',
                'copyright' => null,
                'lyrics' => 'VERSE 1
When troubles come, when darkness crowds
When fortunes fail and loss surrounds
My soul is weak, but Christ is strong
And so to him I leave it all
For he who holds all things
Can bear each care I bring

CHORUS
So, I lay it all on Jesus
Steadfast is the love of Jesus
He hears my cry, he’s faithful
I lay it all on Jesus

VERSE 2
When questions rise, when faith wears thin
When fears come fast, and truth grows dim
The One Who saved will not forsake
I’ll trust his word and trust his way
For he who bore my blame
Can bear each care I name

BRIDGE
I am weak; you are strong
Jesus, come and take it all
All my cares I cast on You

TAG
I lay it all, I lay it all on Jesus',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            8 => [
                'id' => 9,
                'name' => 'It Is Well',
                'ccli_number' => '4509365',
                'copyright' => null,
                'lyrics' => 'When peace like a river, attendeth my way,
When sorrows like sea billows roll;
Whatever my lot, Thou hast taught me to say
It is well, it is well, with my soul.

Though Satan should buffet, though trials should come,
Let this blest assurance control,
That Christ has regarded my helpless estate,
And hath shed His own blood for my soul.

It is well, (it is well),
With my soul, (with my soul)
It is well, it is well, with my soul.

My sin, oh, the bliss of this glorious thought!
My sin, not in part but the whole,
Is nailed to the cross, and I bear it no more,
Praise the Lord, praise the Lord, O my soul!

And Lord, haste the day when the faith shall be sight,
The clouds be rolled back as a scroll;
The trump shall resound, and the Lord shall descend,
Even so, it is well with my soul',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            9 => [
                'id' => 10,
                'name' => 'Turn Your Eyes',
                'ccli_number' => '7057893',
                'copyright' => null,
                'lyrics' => 'VERSE 1
Turn your eyes upon Jesus
Look full in His wonderful face
And the things of earth will grow strangely dim
In the light of His glory and grace

VERSE 2
Turn your eyes to the hillside
Where justice and mercy embraced
There the Son of God gave His life for us
And our measureless debt was erased

CHORUS
Jesus, to You we lift our eyes
Jesus, our glory and our prize
We adore You, behold You, our Savior ever true
Oh Jesus, we turn our eyes to You

VERSE 3
Turn your eyes to the morning
And see Christ the Lion awake
What a glorious dawn, fear of death is gone
For we carry His life in our veins

VERSE 4
Turn your eyes to the heavens
Our King will return for His own
Every knee will bow, every tongue will shout,
‘All glory to Jesus alone!',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            10 => [
                'id' => 11,
                'name' => 'Doxology',
                'ccli_number' => '56204',
                'copyright' => null,
                'lyrics' => '<p>Praise God from whom all blessings flow<br>Praise him all creatures here below<br>Praise him above ye heavenly hosts<br>Praise Father, Son, and Holy Ghost. Amen.</p>',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-07-05 18:22:50',
            ],
            11 => [
                'id' => 12,
                'name' => 'Crown Him',
                'ccli_number' => '7071124',
                'copyright' => null,
                'lyrics' => 'VERSE 1
The humble King has come to earth
From throne on high to lowly birth
His glory reigns
The spotless lamb has washed away
Our fatal sin with saving grace
His glory reigns
The Man of Sorrows crucified
For love He bleeds, and love He dies
His glory reigns

CHORUS 1
Christ the King is Lord, crown Him
Seated on His throne, hail Him

VERSE 2
The resurrected King of Kings
Enthroned on high in majesty
His glory reigns
Behold! The gracious Lord of Light
Has opened ears and poured out sight
His glory reigns

CHORUS 2
Christ the King is Lord, crown Him
Seated on His throne, hail Him
See the lamb adorned, praise Him
Glory in His love, praise Him',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            12 => [
                'id' => 13,
                'name' => 'How Vast The Love',
                'ccli_number' => '7138111',
                'copyright' => null,
                'lyrics' => 'VERSE 1
Come gaze upon your Savior
Behold your great High Priest
Draw near in awe and wonder
His cross has spoken peace

VERSE 2
Come rest from sin and striving
Find endless stores of grace
The heart that turns to Jesus
Is cleansed from every stain

CHORUS
Oh, how deep, how wide, how long
Oh, how vast the love of Jesus
Oh, how sure, how sweet, how strong
Oh, how vast His love for us

VERSE 3
So lift your eyes to Jesus
Arise from doubt and shame
His blood cries, ‘It is finished!’
Our life is in His name

VERSE 4
What now can separate us?
Can death or pain or fear?
We have this strong assurance
In Christ we’ve been brought near
And in His strength we’ll labor
His promises our hope
Thus far His love has led us!
His love will lead us home!',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            13 => [
                'id' => 14,
                'name' => 'All I Have Is Christ',
                'ccli_number' => '5174122',
                'copyright' => null,
                'lyrics' => 'VERSE 1
I once was lost in darkest night
Yet thought I knew the way
The sin that promised joy and life
Had led me to the grave
I had no hope that You would own
A rebel to Your will
And if You had not loved me first
I would refuse You still

VERSE 2
But as I ran my hell-bound race
Indifferent to the cost
You looked upon my helpless state
And led me to the cross
And I beheld God’s love displayed
You suffered in my place
You bore the wrath reserved for me
Now all I know is grace

CHORUS
Hallelujah! All I have is Christ
Hallelujah! Jesus is my life

VERSE 3
Now, Lord, I would be Yours alone
And live so all might see
The strength to follow Your commands
Could never come from me
O Father, use my ransomed life
In any way You choose
And let my song forever be
My only boast is You',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            14 => [
                'id' => 15,
                'name' => 'How Deep The Father\'s Love For Us',
                'ccli_number' => '1558110',
                'copyright' => null,
                'lyrics' => '<p>How deep the Father’s love for us,<br>How vast beyond all measure,<br>That He should give His only Son<br>To make a wretch His treasure.<br>How great the pain of searing loss<br>The Father gives His son away,<br>As wounds which mar the Chosen One<br>Bring many sons to glory.</p><p>Behold the man upon a cross,<br>My sin upon His shoulders;<br>Ashamed, I hear my mocking voice<br>Call out among the scoffers.<br>It was my sin that held Him there<br>Until it was accomplished;<br>His dying breath has brought me life<br>I know that it is finished.</p><p>I will not boast in anything,<br>No gifts, no power, no wisdom;<br>But I will boast in Jesus Christ,<br>His death and resurrection.<br>Why should I gain from His reward?<br>I cannot give an answer;<br>But this I know with all my heart<br>His wounds have paid my ransom.</p>',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-07-19 22:42:41',
            ],
            15 => [
                'id' => 16,
                'name' => 'Doxology (Amen)',
                'ccli_number' => '7059306',
                'copyright' => null,
                'lyrics' => 'VERSE 1
Praise God from whom all blessings flow
Praise him all creatures here below
Praise him all he heavenly hosts
Praise Father, Son, and Holy Ghost

CHORUS
Amen Amen Amen
God we praise You
God we praise You

VERSE 2
Praise God for all that he has done
Praise him for he has overcome
The grave is beaten love has won
Praise God our Savior, Christ the son

BRIDGE
Amen Amen Amen
We praise You
We praise You',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            16 => [
                'id' => 17,
                'name' => 'O God Of Mercy Hear Our Plea',
                'ccli_number' => '7096628',
                'copyright' => null,
                'lyrics' => '<p>O God, we’ve seen Your faithfulness<br>You brought us from the wilderness<br>But now our faith is frail and weak<br>O God of mercy, hear our plea</p><p>When will You comfort our distress?<br>How long until the promised rest?<br>We cry to You from deepest need<br>O God of mercy, hear our plea</p><p>Abba, Father, our Redeemer<br>In this barren land be our hope and strength<br>Until glory we will trust and sing<br>Abba, Father, hear our plea</p><p>We join creation’s longing groan<br>To take Your ransomed children home<br>For then the eyes of all will see<br>The God of mercy hears our plea</p>',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-07-05 20:16:31',
            ],
            17 => [
                'id' => 18,
                'name' => 'Rock Of Ages',
                'ccli_number' => '7004664',
                'copyright' => null,
                'lyrics' => '<p>Rock of Ages, cleft for me <br>Let me hide myself in thee <br>Let the water and the blood <br>from thy wounded side which flowed <br>Be of sin the double cure <br>Save from wrath and make me pure</p><p>Not the labors of my hands <br>Can fulfill the law\'s commands <br>Should my passion never fade <br>And my efforts all be weighed <br>All for sin could not atone<br> You must save and you alone </p><p>Rock of Ages No one takes your life<br> Yet you died that I might live <br>Costly grace you freely give </p><p>Rock of Ages You have paid the price <br>You were cleft to cover me <br>Let my hide myself in thee </p><p>Nothing in my hand I bring <br>Simply to the cross I cling <br>Naked come to thee for dress <br>Helpless look to thee for grace <br>Wretched to the fount I fly <br>Wash me, Savior, or I die </p><p>And while I draw my final breath <br>I\'ll rest upon your grace <br>And when I close my eyes in death<br> I\'ll wake to see your face</p>',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-07-12 14:07:06',
            ],
            18 => [
                'id' => 19,
                'name' => 'Come Praise And Glorify',
                'ccli_number' => '6167664',
                'copyright' => null,
                'lyrics' => 'VERSE 1
Come praise and glorify our God
The Father of our Lord
In Christ He has in heav’nly realms
His blessings on us poured
For pure and blameless in His sight
He destined us to be
And now we’ve been adopted through
His Son eternally

CHORUS
To the praise of Your glory
To the praise of Your mercy and grace
To the praise of Your glory
You are the God who saves

VERSE 2
Come praise and glorify our God
Who gives His grace in Christ
In Him our sins are washed away
Redeemed through sacrifice
In Him God has made known to us
The myst’ry of His will
That Christ should be the head of all
His purpose to fulfill

VERSE 3
Come praise and glorify our God
For we’ve believed the Word
And through our faith we have a seal
The Spirit of the Lord
The Spirit guarantees our hope
Until redemption’s done
Until we join in endless praise
To God, the Three in One',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            19 => [
                'id' => 20,
                'name' => 'Behold Our God',
                'ccli_number' => '5937510',
                'copyright' => null,
                'lyrics' => '<p>Who has held the oceans in His hands?<br>Who has numbered every grain of sand?<br>Kings and nations tremble at His voice<br>All creation rises to rejoice</p><p>Behold our God seated on His throne<br>Come, let us adore Him<br>Behold our King! Nothing can compare<br>Come, let us adore Him!</p><p>Who has given counsel to the Lord?<br>Who can question any of His words?<br>Who can teach the One Who knows all things?<br>Who can fathom all His wondrous deeds?</p><p>Who has felt the nails upon His hands<br>Bearing all the guilt of sinful man?<br>God eternal humbled to the grave<br>Jesus, Savior risen now to reign!</p><p>Men: You will reign forever<br>Women: Let Your glory fill the earth</p>',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-08-23 17:34:46',
            ],
            20 => [
                'id' => 21,
                'name' => 'On Christ The Solid Rock I Stand',
                'ccli_number' => '3809729',
                'copyright' => null,
                'lyrics' => '<p>My hope is built on nothing less<br>than Jesus\' Christ, my righteousness;<br>I dare not trust the sweetest frame,<br>but wholly lean on Jesus\' name.</p><p>On Christ, the solid rock, I stand;<br>All other ground is sinking sand,<br>All other ground is sinking sand.</p><p>When darkness veils his lovely face,<br>I rest on his unchanging grace;<br>in ev\'ry high and stormy gale,<br>my anchor holds within the veil.</p><p>His oath, his covenant, his blood<br>support me in the whelming flood;<br>when all around my soul gives way,<br>He is all my hope and stay.</p><p>When he shall come with trumpet sound,<br>O may I then in him be found,<br>dressed in his righteousness alone,<br>faultless to stand before the throne.</p>',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-07-12 14:15:26',
            ],
            21 => [
                'id' => 22,
                'name' => 'He Will Hold Me Fast',
                'ccli_number' => '7016161',
                'copyright' => null,
                'lyrics' => 'VERSE 1
When I fear my faith will fail, Christ will hold me fast;
When the tempter would prevail, He will hold me fast.
I could never keep my hold through life\'s fearful path;
For my love is often cold; He must hold me fast.

CHORUS
He will hold me fast, He will hold me fast;
For my Saviour loves me so, He will hold me fast.

VERSE 2
Those He saves are His delight, Christ will hold me fast;
Precious in his holy sight, He will hold me fast.
He\'ll not let my soul be lost; His promises shall last;
Bought by Him at such a cost, He will hold me fast.

VERSE 3
For my life He bled and died, Christ will hold me fast;
Justice has been satisfied; He will hold me fast.
Raised with Him to endless life, He will hold me fast
‘Till our faith is turned to sight, When He comes at last!',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            22 => [
                'id' => 23,
                'name' => 'Holy Holy Holy (Nicaea)',
                'ccli_number' => '1156',
                'copyright' => null,
                'lyrics' => '<p>Holy, holy, holy! Lord God Almighty!<br>Early in the morning our song shall rise to thee;<br>Holy, holy, holy! merciful and mighty,<br>God in three persons, blessed Trinity!</p><p>Holy, holy, holy! All the saints adore thee,<br>Casting down their golden crowns around the glassy sea;<br>Cherubim and seraphim falling down before thee,<br>Who wert and art and evermore shalt be.</p><p>Holy, holy, holy! Though the darkness hide thee,<br>Though the eye of sinful man thy glory may not see,<br>Only thou art holy; there is none beside thee,<br>Perfect in power, in love, and purity.</p><p>Holy, holy, holy! Lord God Almighty!<br>All thy works shall praise thy name, in earth and sky and sea;<br>Holy, holy, holy! merciful and mighty,<br>God in three persons, blessed Trinity! </p>',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-08-23 17:33:21',
            ],
            23 => [
                'id' => 24,
                'name' => 'Thirst',
                'ccli_number' => '7003126',
                'copyright' => null,
                'lyrics' => 'VERSE 1
Wash over me like a tidal wave
Clean out what pulls me to the grave
Nothing left that you don\'t love

Take me where your river flows
Heal the desert in my soul
Let it wash over my feet
All I\'m asking for is just a drink

CHORUS
I thirst for You
Yes my soul it thirsts for You
Even as the deer is panting for the stream
Even though my soul is thirsty
I thirst for you

VERSE 2
Spirit of the living God
Would you fall afresh like rain on us
Burst the doors and flood the halls
Into forgotten rooms inside our hearts

And we will all be swept away
In the current of your love and grace
Living water flow to me
All I\'m asking for is just a drink

CHORUS
I thirst for you
Yes my soul it thirsts for you
Even as the deer is panting for the stream
Even though my soul is thirsty
I thirst for you

BRIDGE
One thing I ask and I would seek
To see You there in front of me
With nothing standing in the way
Just me before You unashamed

I thirst for you
I thirst for you
You\'re the well that won\'t run dry
Only you can satisfy

I thirst for you
I thirst for you
Living water flow to me
All I ask is just one drink
I thirst for you',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            24 => [
                'id' => 25,
                'name' => 'O Lord My Rock And My Redeemer',
                'ccli_number' => '7096627',
                'copyright' => null,
                'lyrics' => 'VERSE 1
O Lord, my Rock and my Redeemer
Greatest treasure of my longing soul
My God, like You there is no other
True delight is found in You alone
Your grace, a well too deep to fathom
Your love exceeds the heavens’ reach
Your truth, a fount of perfect wisdom
My highest good and my unending need

VERSE 2
O Lord, my Rock and my Redeemer
Strong defender of my weary heart
My sword to fight the cruel deceiver
And my shield against his hateful darts
My song when enemies surround me
My hope when tides of sorrow rise
My joy when trials are abounding
Your faithfulness, my refuge in the night

VERSE 3
O Lord, my Rock and my Redeemer
Gracious Savior of my ruined life
My guilt and cross laid on Your shoulders
In my place You suffered bled and died
You rose, the grave and death are conquered
You broke my bonds of sin and shame
O Lord, my Rock and my Redeemer
May all my days bring glory to Your Name',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            25 => [
                'id' => 26,
                'name' => 'Living Hope',
                'ccli_number' => '7106807',
                'copyright' => null,
                'lyrics' => 'VERSE 1
How great the chasm that lay between us
How high the mountain I could not climb
In desperation, I turned to heaven
And spoke Your name into the night
Then through the darkness, Your loving-kindness
Tore through the shadows of my soul
The work is ﬁnished, the end is written
Jesus Christ, my living hope

VERSE 2
Who could imagine so great a mercy?
What heart could fathom such boundless grace?
The God of ages stepped down from glory
To wear my sin and bear my shame
The cross has spoken, I am forgiven
The King of kings calls me His own
Beautiful Savior, I\'m Yours forever
Jesus Christ, my living hope

CHORUS
Hallelujah, praise the One who set me free
Hallelujah, death has lost its grip on me
You have broken every chain
There\'s salvation in Your name
Jesus Christ, my living hope

VERSE 3
Then came the morning that sealed the promise
Your buried body began to breathe
Out of the silence, the Roaring Lion
Declared the grave has no claim on me
Then came the morning that sealed the promise
Your buried body began to breathe
Out of the silence, the Roaring Lion
Declared the grave has no claim on me
Jesus, Yours is the victory, whoa!

BRIDGE
Jesus Christ, my living hope
Oh God, You are my living hope',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            26 => [
                'id' => 27,
                'name' => 'Above All',
                'ccli_number' => '2672885',
                'copyright' => null,
                'lyrics' => 'VERSE 1
Above all powers above all kings
Above all nature and all created things
Above all wisdom and all the ways of man
You were here before the world began

Above all kingdoms, above all thrones
Above all wonders the world has ever known
Above all wealth and treasures of the earth
There\'s no way to measure what You\'re worth

CHORUS 1
Crucified laid behind a stone, You lived to die
Rejected and alone
Like a rose trampled on the ground
You took the fall and thought of me above all

BRIDGE
Like a rose trampled on the ground
You took the fall and thought of me above all',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            27 => [
                'id' => 28,
                'name' => 'As It Is In Heaven',
                'ccli_number' => '7059303',
                'copyright' => null,
                'lyrics' => 'Let my heart be a temple
Let that temple have a throne
Let the one who sits upon it
Be You and You alone

I surrender my ambitions
I lay down all my pride
That I would be Your servant
And You would be my God

Let Your will be done in me
Let Your kingdom come in me
In my life Lord let it be
As it is in heaven

Like a battle weary soldier
I\'m lifting up my hands
In absolute surrender
My life, my will, my plans

Be enthroned upon our hearts
Take control of every part
Be the King of all we are
Oh God in heaven

Yours will be the glory
The honor and the fame
And this will be my story
Lifting high Your name

I lift my hands and say that I need You
I lift my heart and say that I love You
I give my life, God I am forever Yours',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            28 => [
                'id' => 29,
                'name' => 'Reformation Song',
                'ccli_number' => '7088632',
                'copyright' => null,
                'lyrics' => '<p>Your Word alone is solid ground,<br>The mighty rock on which we build;<br>In every line the truth is found<br>And every page with glory filled.</p><p>Through faith alone we come to You;<br>We have no merit we can claim.<br>Sure that Your promises are true,<br>We place our hope in Jesus’ name.</p><p>Gloria, gloria, glory to God alone;<br>Gloria, gloria, glory to God alone.</p><p>In Christ alone we’re justified;<br>His righteousness is all our plea;<br>Your law’s demands are satisfied;<br>His perfect work has set us free.</p><p>By grace alone we have been saved;<br>All that we are has come from You.<br>Hearts that were once by sin enslaved<br>Now by Your pow’r have been made new.</p><p>VERSE 5 - OPTIONAL<br>And on this Reformation day<br>We join with saints of old to sing <br>We lift our hearts as one in praise <br>Glory to Christ our gracious King</p>',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-08-09 18:02:25',
            ],
            29 => [
                'id' => 30,
                'name' => 'What A Beautiful Name',
                'ccli_number' => '7068424',
                'copyright' => null,
                'lyrics' => 'VERSE 1
You were the Word at the beginning
One with God the Lord Most High
Your hidden glory in creation
Now revealed in You our Christ

CHORUS 1
What a beautiful Name it is
What a beautiful Name it is
The Name of Jesus Christ my King
What a beautiful Name it is
Nothing compares to this
What a beautiful Name it is
The Name of Jesus

VERSE 2
You didn’t want heaven without us
So Jesus You brought heaven down
My sin was great Your love was greater
What could separate us now

CHORUS 2
What a wonderful Name it is
What a wonderful Name it is
The Name of Jesus Christ my King
What a wonderful Name it is
Nothing compares to this
What a wonderful Name it is
The Name of Jesus
What a wonderful Name it is
The Name of Jesus

BRIDGE
Death could not hold You
The veil tore before You
You silence the boast of sin and grave
The heavens are roaring
The praise of Your glory
For You are raised to life again

You have no rival
You have no equal
Now and forever God You reign
Yours is the kingdom
Yours is the glory
Yours is the Name above all names

CHORUS 3
What a powerful Name it is
What a powerful Name it is
The Name of Jesus Christ my King
What a powerful Name it is
Nothing can stand against
What a powerful Name it is
The Name of Jesus

TAGS
What a powerful Name it is The Name of Jesus
What a powerful Name it is The Name of Jesus',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            30 => [
                'id' => 31,
                'name' => 'In Christ Alone',
                'ccli_number' => '3350395',
                'copyright' => null,
                'lyrics' => '<p>In Christ alone my hope is found;<br>He is my light, my strength, my song;<br>This cornerstone, this solid ground,<br>Firm through the fiercest drought and storm.<br>What heights of love, what depths of peace,<br>When fears are stilled, when strivings cease!<br>My comforter, my all in all—<br>Here in the love of Christ I stand.</p><p>In Christ alone, Who took on flesh,<br>Fullness of God in helpless babe!<br>This gift of love and righteousness,<br>Scorned by the ones He came to save.<br>Till on that cross as Jesus died,<br>The wrath of God was satisfied;<br>For ev\'ry sin on Him was laid—<br>Here in the death of Christ I live.</p><p>There in the ground His body lay,<br>Light of the world by darkness slain;<br>Then bursting forth in glorious day,<br>Up from the grave He rose again!<br>And as He stands in victory,<br>Sin\'s curse has lost its grip on me;<br>For I am His and He is mine—<br>Bought with the precious blood of Christ.</p><p>No guilt in life, no fear in death—<br>This is the pow\'r of Christ in me;<br>From life\'s first cry to final breath,<br>Jesus commands my destiny.<br>No pow\'r of hell, no scheme of man,<br>Can ever pluck me from His hand;<br>Till He returns or calls me home—<br>Here in the pow\'r of Christ I\'ll stand.</p>',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-08-23 17:36:19',
            ],
            31 => [
                'id' => 32,
                'name' => 'There Is A Fountain',
                'ccli_number' => '27707',
                'copyright' => null,
                'lyrics' => '<p>There is a fountain filled with blood,<br>Drawn from Immanuel’s veins,<br>And sinners plunged beneath that flood<br>Lose all their guilty stains: (3x)<br>And sinners plunged beneath that flood<br>Lose all their guilty stains.</p><p>The dying thief rejoiced to see<br>That fountain in His day;<br>And there have I, though vile as he,<br>Washed all my sins away, (3x)<br>And there have I, though vile as he,<br>Washed all my sins away.</p><p>Dear dying Lamb, Thy precious blood<br>Shall never lose its pow’r,<br>Till all the ransomed church of God<br>Are safe, to sin no more, (3x)<br>Till all the ransomed church of God<br>Are safe, to sin no more.</p><p>E’er since by faith I saw the stream<br>Thy flowing wounds supply,<br>Redeeming love has been my theme,<br>And shall be till I die, (3x)<br>Redeeming love has been my theme,<br>And shall be till I die.</p><p>When this poor, lisping, stamm’ring tongue<br>Lies silent in the grave,<br>Then in a nobler, sweeter song,<br>I’ll sing Thy pow’r to save, (3x)<br>Then in a nobler, sweeter song,<br>I’ll sing Thy pow’r to save.</p>',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-08-09 18:21:32',
            ],
            32 => [
                'id' => 33,
                'name' => 'Jesus There\'s No One Like You',
                'ccli_number' => '7096630',
                'copyright' => null,
                'lyrics' => 'VERSE 1
There is no song we could sing
To honor the weight of Your glory
There are no words we could speak
To capture the depth of Your beauty

CHORUS
Jesus, there’s no one like You
Jesus, we love You, ever adore You
There’s no one like You
Jesus, we love You, ever adore You, Lord

VERSE 2
There is no sinner beyond
The infinite stretch of Your mercy
How can we thank You enough
For how You have loved us completely?

BRIDGE
All we have
All we need
All we want is You',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            33 => [
                'id' => 34,
                'name' => 'O Praise The Name (Anástasis)',
                'ccli_number' => '7037787',
                'copyright' => null,
                'lyrics' => 'I cast my mind to Calvary
Where Jesus bled and died for me
I see His wounds, His hands, His feet
My Saviour on that cursed tree

His body bound and drenched in tears
They laid Him down in Joseph\'s tomb
The entrance sealed by heavy stone
Messiah still and all alone

O praise the name of the Lord our God
O praise His name forevermore
For endless days we will sing Your praise
Oh Lord, oh Lord our God

And then on the third at break of dawn
The Son of heaven rose again
O trampled death where is your sting?
The angels roar for Christ the King

O praise the name of the Lord our God
O praise His name forevermore
For endless days we will sing Your praise
Oh Lord, oh Lord our God

He shall return in robes of white
The blazing sun shall pierce the night
And I will rise among the saints
My gaze transfixed on Jesus\' face

O praise the name of the Lord our God
O praise His name forever more
For endless days we will sing Your praise
Oh Lord, oh Lord our God
Oh Lord, oh Lord our God
Oh Lord, oh Lord our God',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            34 => [
                'id' => 35,
                'name' => 'Fall Afresh',
                'ccli_number' => '6032768',
                'copyright' => null,
                'lyrics' => 'Awaken my soul, come awake
To hunger, to seek, to thirst
Awaken first love, come awake
And do as You did, at first!

Spirit of the living God
Come fall afresh on me
Come wake me from my sleep
Blow through the caverns of my soul
Pour in me to overflow!
To overflow

Spirit come and fill this place
Let Your glory now invade
Spirit come and fill this place
Let Your glory now invade

Spirit of the living God
Come fall afresh on me
Come wake me from my sleep
Blow through the caverns of my soul
Pour in me to overflow!',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            35 => [
                'id' => 36,
                'name' => 'Jesus Paid It All',
                'ccli_number' => '4689508',
                'copyright' => null,
                'lyrics' => '<p>I hear the Savior say,<br>Thy strength indeed is small;<br>Child of weakness, watch and pray,<br>Find in Me thine all in all.</p><p>Jesus paid it all,<br>All to Him I owe;<br>Sin had left a crimson stain,<br>He washed it white as snow.</p><p>Lord, now indeed I find<br>Thy power and Thine alone,<br>Can change the leper\'s spots<br>and melt the heart of stone.</p><p>And when before the throne<br>I stand in Him complete,<br>Jesus died my soul to save,<br>my lips shall still repeat</p><p>O Praise the one who paid my debt<br>And raised this life up from the dead<br>O Praise the one who paid my debt<br>And raised this life up from the dead</p>',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-08-09 18:20:13',
            ],
            36 => [
                'id' => 37,
                'name' => 'I Will Exalt You',
                'ccli_number' => '5409079',
                'copyright' => null,
                'lyrics' => 'I will exalt you
I will exalt you
I will exalt you
You are my God

My hiding place
My safe refuge
My treasure lord you are
My friend and king
Anointed one
Most holy

Because you\'re with me
Because you\'re with me
Because you\'re with me
I will not fear',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            37 => [
                'id' => 38,
                'name' => 'Great Are You Lord',
                'ccli_number' => '6460220',
                'copyright' => null,
                'lyrics' => 'VERSE 1
You give life, You are love
You bring light to the darkness
You give hope, You restore
Every heart that is broken
Great are You, Lord

CHORUS
So we pour out our praise
We pour out our praise
It\'s Your breath in our lungs
So we pour out our praise to You only

CHORUS 2
It\'s Your breath in our lungs
So we pour out our praise
We pour out our praise
It\'s Your breath in our lungs
So we pour out our praise to You only
It\'s Your breath in our lungs
So we pour out our praise
We pour out our praise
It\'s Your breath in our lungs
So we pour out our praise to You only

BRIDGE
All the earth will shout Your praise
Our hearts will cry, these bones will sing
Great are You, Lord',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            38 => [
                'id' => 39,
                'name' => 'We Fall Down',
                'ccli_number' => '2437367',
                'copyright' => null,
                'lyrics' => 'We fall down
We lay our crowns
At the feet of Jesus
The greatness of
Mercy and love
At the feet of Jesus

And we cry holy holy holy
And we cry holy holy holy
And we cry holy holy holy
Is the Lamb

Holy holy holy
Is the Lamb',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            39 => [
                'id' => 40,
                'name' => 'We Exalt Thee',
                'ccli_number' => '17803',
                'copyright' => null,
                'lyrics' => 'For Thou, O Lord, art high above all the earth.
Thou art exalted far above all gods.
For Thou, O Lord, art high above all the earth.
Thou art exalted far above all gods.

We exalt Thee, we exalt Thee, we exalt Thee, O Lord.
We exalt Thee, we exalt Thee, we exalt Thee, O Lord.',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            40 => [
                'id' => 41,
                'name' => 'Christ Is Enough',
                'ccli_number' => '6514035',
                'copyright' => null,
                'lyrics' => 'Christ is my reward and all of my devotion
Now there\'s nothing in this world that could ever satisfy
Through every trial my soul will sing
No turning back, I\'ve been set free

Christ is enough for me, Christ is enough for me
Everything I need is in You, everything I need

Christ my all in all, the joy of my salvation
And this hope will never fail, Heaven is our home
Through every storm my soul will sing
Jesus is here, to God be the glory

I have decided to follow Jesus
No turning back, no turning back
The cross before me, the world behind me
No turning back, no turning back',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            41 => [
                'id' => 42,
                'name' => 'Refiner\'s Fire',
                'ccli_number' => '426298',
                'copyright' => null,
                'lyrics' => 'Purify my heart
Let me be as gold
And precious silver
Purify my heart
Let me be as gold
Pure gold

Refiner\'s fire
My heart\'s one desire
Is to be holy
Set apart for You Lord
I choose to be holy
Set apart for You My Master
Ready to do Your will

Purify my heart
Cleanse me from within
And make me holy
Purify my heart
Cleanse me from my sin
Deep within',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            42 => [
                'id' => 43,
                'name' => 'More Love More Power',
                'ccli_number' => '60661',
                'copyright' => null,
                'lyrics' => 'More love, more power 
More of You in my life
 More love, more power
 More of You in my life

And I will worship You, with all of my heart
 I will worship You, with all of my mind 
I will worship You, with all of my strength 
For You are my Lord
',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            43 => [
                'id' => 44,
                'name' => 'One Thing I Ask',
                'ccli_number' => '213353',
                'copyright' => null,
                'lyrics' => 'One thing I ask, one thing I seek
That I may dwell in your house, oh lord
All of my days, all of my life
That I may see you lord
Hear me oh lord, hear me when I cry
Lord do not hide your face from me
You have been my strength, you have been my shield
And you will lift me up

One thing I ask, one thing I desire,
Is to see you, is to see you',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            44 => [
                'id' => 45,
                'name' => 'Good To Me',
                'ccli_number' => '313480',
                'copyright' => null,
                'lyrics' => 'I cry out
For your hand of mercy to heal me
I am weak
I need your love to free me

Oh Lord, my Rock
My strength in weakness
Come rescue me, oh Lord

You are my hope
Your promise never fails me
And my desire
Is to follow You forever

For You are good
For You are good
For You are good to me',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            45 => [
                'id' => 46,
                'name' => 'We Worship At Your Feet',
                'ccli_number' => '192405',
                'copyright' => null,
                'lyrics' => 'Come and see, come and see
Come and see the King of love
See the purple robe and crown of thorns he wears
Soldiers mock, rulers sneer
As he lifts the cruel cross
Lone and friendless now he climbs towards the hill

We worship at your feet
Where wrath and mercy meet
And guilty one\'s are washed
By love\'s pure stream
For us he was made sin
Oh, help me take it in
Deep wounds of love cry out \'Father, forgive\'
I worship, I worship
The Lamb who was slain.

Come and weep, come and mourn
For your sin that pierced him there
So much deeper than the wounds of thorn and nail
All our pride, all our greed
All our fallenness and shame
And the Lord has laid the punishment on him

Word of God, born to earth
To redeem men marred by sin
Here we bow in awe before
Your searching eyes
From your tears comes our joy
From your death new life has come
By your resurrection power we shall rise',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            46 => [
                'id' => 47,
                'name' => 'Glory',
                'ccli_number' => '7003038',
                'copyright' => null,
                'lyrics' => 'The mountains standing in Your strength
The oceans roaring out Your praise
All creation glorifies Your name

The angels bow before Your throne
The heavens shine for You alone
All creation glorifies Your name
All creation glorifies Your name
Singing

Holy, Holy, Holy is the Lord
Almighty, Worthy
All the earth is filled with Your glory, glory
We give You glory, glory

In Your hands You hold the universe
At Your feet, the nations of the earth
All creation glorifies Your name
All creation glorifies Your name
Singing

Blessing, honor, glory, power
All our praises, Yours forever',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            47 => [
                'id' => 48,
                'name' => 'O Come To The Altar',
                'ccli_number' => '7051511',
                'copyright' => null,
                'lyrics' => 'Are you hurting and broken within
Overwhelmed by the weight of your sin
Jesus is calling
Have you come to the end of yourself
Do you thirst for a drink from the well
Jesus is calling

O come to the altar
The Father\'s arms are open wide
Forgiveness was bought with
The precious blood of Jesus Christ

Leave behind your regrets and mistakes
Come today there\'s no reason to wait
Jesus is calling
Bring your sorrows and trade them for joy
From the ashes a new life is born
Jesus is calling

Oh what a Savior
Isn\'t He wonderful
Sing alleluia, Christ is risen
Bow down before Him
For He is Lord of all
Sing alleluia, Christ is risen

Bear your cross as you wait for the crown
Tell the world of the treasure you\'ve found',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            48 => [
                'id' => 49,
                'name' => 'White As Snow',
                'ccli_number' => '5496329',
                'copyright' => null,
                'lyrics' => 'Have mercy on me, oh God
According to Your unfailing love
According to Your great compassion
Blot out my transgressions

Would You create in me a clean heart
Oh God
Restore in me
The joy of Your salvation

The sacrifices of our God
Are a broken and contrite heart
Against you and you alone have I sinned

Wash me white as snow
And I will be made whole
Wash me white as snow
',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            49 => [
                'id' => 50,
                'name' => 'I Give You My Heart',
                'ccli_number' => '1866132',
                'copyright' => null,
                'lyrics' => 'This is my desire
To honor you
Lord with all my heart
I worship you
All I have within me
I give You praise
All that I adore is in you

Lord, I give you my heart
I give you my soul
I live for you alone
Every breath that I take
Every moment I\'m awake
Lord, have your way in me',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            50 => [
                'id' => 51,
                'name' => 'Unto You',
                'ccli_number' => '6415701',
                'copyright' => null,
                'lyrics' => 'All creation cries your name
Falling down before your throne
Where the mighty anthem reigns
Unto you alone

There is no greater God than our king
There is no greater song we could sing

For you are, holy
Lord you are, worthy
Unto you, glory in the highest

All the kingdoms of this world
Will surrender to you God
Oh forever you will stand
And reign victorious

Every creature great and small
Saints and angels one and all
Bow in reverence at your feet',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            51 => [
                'id' => 52,
                'name' => 'Father You Are All We Need',
                'ccli_number' => '7035487',
                'copyright' => null,
                'lyrics' => 'VERSE 1
Our Father who in Heaven reigns
How great and mighty is Your name
Your kingdom come, Your will be done
Now here on earth as is above

Oh, give to us our daily bread
And keep our hungry spirits fed
May all our satisfaction be
In You whose grace has set us free

CHORUS
Give us hope, give us faith
Help us trust in Your guidance
From the depths of Your grace
You have richly provided
Thank You, thank You
Father, You are all we need
Father, You are all we need

VERSE 2
Forgive us all our trespasses
As we forgive when sinned against
Though evil seeks to hide Your face
We fix our eyes on You by faith

BRIDGE
We lift You high above all names
Your kingdom will forever reign
To You the glory and the power forevermore
We lift You high above all names
Your kingdom will forever reign
To You the glory and the power forevermore',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            52 => [
                'id' => 53,
                'name' => 'Messiah',
                'ccli_number' => '659728',
                'copyright' => null,
                'lyrics' => 'Messiah, Deliverer,
Jesus Christ, God\'s own son
Our Saviour, Redeemer,
God\'s own Christ, the Chosen One
Messiah, Deliverer,
Jesus Christ, God\'s own son
Our Saviour, Redeemer,
God\'s own Christ, the Chosen One

I will give praises to the Holy One
I will give praise to the Lamb
Praises to the Holy One
I will give praise to the Lamb',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            53 => [
                'id' => 54,
                'name' => '\'Tis So Sweet To Trust In Jesus (Trust In Jesus)',
                'ccli_number' => '22609',
                'copyright' => null,
                'lyrics' => '<p>Tis so sweet to trust in Jesus, </p><p>Just to take Him at His word; Just to rest upon His promise; Just to know, Thus saith the Lord. Jesus, Jesus, how I trust Him, How I’ve proved Him o’er and o’er, Jesus, Jesus, Precious Jesus! O for grace to trust Him more. O how sweet to trust in Jesus, Just to trust His cleansing blood; Just in simple faith to plunge me, ’Neath the healing, cleansing flood. Yes, ’tis sweet to trust in Jesus, Just from sin and self to cease; Just from Jesus simply taking Life, and rest, and joy, and peace. I’m so glad I learned to trust Thee, Precious Jesus, Savior, Friend; And I know that Thou art with me, Wilt be with me to the end.</p>',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-04 02:53:58',
            ],
            54 => [
                'id' => 55,
                'name' => 'Before The Throne Of God Above',
                'ccli_number' => '2306412',
                'copyright' => null,
                'lyrics' => '<p>Before the throne of God above<br>I have a strong and perfect plea<br>A great High Priest whose name is love<br>Who ever lives and pleads for me<br>My name is graven on His hands<br>My name is written on His heart<br>I know that while in heav\'n He stands<br>No tongue can bid me thence depart<br>No tongue can bid me thence depart</p><p>Hallelujah, praise the one, the Son of God<br>Hallelujah, praise the one, Jesus Christ</p><p>When Satan tempts me to despair<br>And tells me of the guilt within<br>Upward I look and see Him there<br>Who made an end to all my sin<br>Because the sinless Savior died<br>My sinful soul is counted free<br>For God the Just is satisfied<br>To look on Him and pardon me<br>To look on Him and pardon me</p><p>Behold Him there, the risen Lamb<br>My perfect, spotless Righteousness<br>The great unchangeable I Am<br>The King of glory and of grace<br>One with Himself, I cannot die<br>My soul is purchased by His blood<br>My life is hid with Christ on high<br>With Christ my Savior and my God</p>',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-07-05 20:17:44',
            ],
            55 => [
                'id' => 56,
                'name' => 'Father Me',
                'ccli_number' => '',
                'copyright' => null,
                'lyrics' => 'VERSE 1
Father, come father me
For your wisdom is what I seek
Instruct me in your word and give me eyes to see
Father, father me

VERSE 2
Father, come set me free
Break any chains that are holding me
Forgive my sin and wash my conscience clean
Father, father me

CHORUS 1
From time everlasting
In love you adopted me
Abba father
Your grace abounds to me

VERSE 3
Father, reveal your will to me
Where you are is where I want to be
For you I thirst; your face is all I seek
Father, father me

VERSE 4
Father, give me a heart of love
Conform me to the image of your son
Cause me grow in grace and in maturity
Father, father me

CHORUS 2
With firmness and mercy
Your hand has guided me
Abba father
Your grace abounds to me

VERSE 5
Father, lead me to my home
I long to hear the words “Come in; well done”
To enter your presence and your joy eternally
Father, father me',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            56 => [
                'id' => 57,
                'name' => 'Exalt The Lord',
                'ccli_number' => '768066',
                'copyright' => null,
                'lyrics' => 'Exalt the Lord our God, (echo)
And worship at His feet. (echo)

Exalt the Lord our God, (echo)
And worship at His feet. (echo)
For He is Holy

For the Lord our God, He is holy
For the Lord our God, He is holy
For the Lord our God, He is holy

Yes, the Lord our God, He is holy',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            57 => [
                'id' => 58,
                'name' => 'In the Shadow',
                'ccli_number' => '',
                'copyright' => null,
                'lyrics' => 'VERSE 1
In the shadow of your wings
I will take my refuge in your strength
And the gates of hell won\'t prevail
O King of Israel

CHORUS
All hail Jehova God, Yeshua
All hail the great I AM, hallelujah
Jesus, King of Israel
We love you, King of Israel',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            58 => [
                'id' => 59,
                'name' => 'Lion Of Judah',
                'ccli_number' => '1079280',
                'copyright' => null,
                'lyrics' => 'You\'re the Lion of Judah, the Lamb that was slain
You ascended to heaven and ever more will reign
At the end of the age when the earth You reclaim
You will gather the nations before You

And the eyes of all men will be fixed on the Lamb
Who was crucified
With wisdom and mercy and justice You\'ll reign
At Your Father\'s side

And the angels will cry: "Hail the Lamb
Who was slain for the world, Rule in power"
And the earth will reply: "You shall reign
As the King of all kings and the Lord of all lords"

There\'s a shield in our hand and a sword at our side
There\'s a fire in our spirits that cannot be denied
As the Father has told us for these You have died
For the nations that gather before You

And the ears of all men need to hear of the Lamb
Who was crucified
Who suffered and died yet was raised up to reign
At the Father\'s side',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            59 => [
                'id' => 60,
                'name' => 'Amazing Love',
                'ccli_number' => '192553',
                'copyright' => null,
                'lyrics' => 'My Lord what love is this
That pays so dearly
That I, the guilty one may go free

Amazing love, o what sacrifice
The Son of God given for me
My debt He pays and my death He dies
That I might live
That I might live

And so they watched Him die
Despised, rejected
But oh, the blood He shed flowed for me

And now this love of Christ
Shall flow like rivers
Come wash your guilt away, live again',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            60 => [
                'id' => 61,
                'name' => 'Great Is Your Love',
                'ccli_number' => '',
                'copyright' => null,
                'lyrics' => 'The steadfast love of the Lord never ceases,
His mercies never come to an end
They are new every morning, new every morning
Great is your love, O Lord, O Lord, O!
Great is your love, O Lord.

Where can I flee away from your presence,
No matter where I run you are there
You are ever beside me, ever beside me
Great is your love, O Lord, O Lord, O!
Great is your love, O Lord.

The Lord our God is good,
His love endures forevermore!
Not death not life, nor height nor depth,
Can separate us from your love, Lord
Great is your faithful love.

We will draw near with hearts of assurance,
hold to our hope without wavering
He who promised is faithful, his promise is faithful
Great is your love, O Lord, O Lord, O!
Great is your love, O Lord.

We once were lost but in mercy you saved us,
Your kindness drew us into your arms
You have made us your children, made us your children
Great is your love, O Lord, O Lord, O!
Great is your love, O Lord.',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            61 => [
                'id' => 62,
                'name' => 'You Are Good',
                'ccli_number' => '3383788',
                'copyright' => null,
                'lyrics' => 'Lord, You are good
And Your mercy endureth forever
Lord, You are good
And Your mercy endureth forever

People from every nation and tongue
From generation to generation

We worship you
Hallelujah, Hallelujah
We worship you for who you are

We worship you
Hallelujah, Hallelujah
We worship you for who you are
Who you are, who you are

You are good all the time
All the time You are good
You are good all the time
All the time You are good',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            62 => [
                'id' => 63,
                'name' => 'Crown Him With Many Crowns',
                'ccli_number' => '7034991',
                'copyright' => null,
                'lyrics' => 'Crown him with many crowns,
The Lamb upon his throne.
Hark! How the heavenly anthem drowns
All music but its own.
Awake, my soul, and sing of him
Who died for thee,
And hail him as thy matchless King
Through all eternity.

Crown him the Lord of life,
Who triumphed over the grave,
And rose victorious in the strife
For those he came to save.
Whose glories now we sing,
Who died, and rose on high,
Who died eternal life to bring,
And lives that death may die.

Crown him the Lord of love,
Behold his hands and side,
Those wounds, yet visible above,
In beauty glorified.
All hail, Redeemer, hail
For thou has died for me
They praise and glory shall not fail
Through all eternity.

Crown him the Lord of peace,
Whose power a scepter sways
From pole to pole, that wars may cease,
And all be prayer and praise.
His reign shall know no end,
And round his pierced feet
Fair flowers of paradise extend
Their fragrance ever sweet.

Crown him the Lord of years,
The Potentate of time,
Creator of the rolling spheres,
Ineffably sublime.
All hail, Redeemer, hail!
For thou has died for me;
Thy praise and glory shall not fail
Throughout eternity.
',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            63 => [
                'id' => 64,
                'name' => 'Hark The Herald Angels Sing',
                'ccli_number' => '27738',
                'copyright' => null,
                'lyrics' => 'Hark! The herald angels sing,
“Glory to the newborn King;
Peace on earth, and mercy mild,
God and sinners reconciled!”
Joyful, all ye nations rise,
Join the triumph of the skies;
With th’angelic host proclaim,
“Christ is born in Bethlehem!”

Hark! the herald angels sing,
“Glory to the newborn King!”

Christ, by highest Heav’n adored;
Christ the everlasting Lord;
Late in time, behold Him come,
Offspring of a virgin’s womb.
Veiled in flesh the Godhead see;
Hail th’incarnate Deity,
Pleased with us in flesh to dwell,
Jesus our Emmanuel

Hail the heav’nly Prince of Peace!
Hail the Sun of Righteousness!
Light and life to all He brings,
Ris’n with healing in His wings.
Mild He lays His glory by,
Born that man no more may die;
Born to raise the sons of earth,
Born to give them second birth.',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            64 => [
                'id' => 65,
                'name' => 'Joy To The World',
                'ccli_number' => '24016',
                'copyright' => null,
                'lyrics' => 'Joy to The world! the Lord is come
Let earth receive her King
Let ev\'ry heart prepare him room
And heaven and nature sing
And heaven and nature sing
And heaven, and heaven,  and nature sing

Joy to the world! the Savior reigns
Let men their songs employ
While fields and floods, rocks, hills and plains
Repeat the sounding joy
Repeat the sounding joy
Repeat repeat the sounding joy

No more let sins and sorrows grow
Nor thorns infest the ground
He comes to make His blessings flow
Far as the curse is found
Far as the curse is found
Far as, far as the curse is found

He rules the world with truth and grace
And makes the nations prove
The glories of His righteousness
And wonders of His love
And wonders of His love
And wonder wonders of His love

Joyful, joyful we adore Thee
God of glory, Lord of love
And hearts unfold like flowers before Thee
Opening to the sun above',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            65 => [
                'id' => 66,
                'name' => 'O Holy Night',
                'ccli_number' => '32015',
                'copyright' => null,
                'lyrics' => 'O holy night, the stars are brightly shining,
It is the night of our dear Savior’s birth.
Long lay the world in sin and error pining,
Till He appeared and the soul felt its worth.
A thrill of hope, the weary world rejoices,
For yonder breaks a new and glorious morn.
Fall on your knees, O hear the angel voi-ces!
O night divine, O night when Christ was born!
O night divine, o night when christ was born!

Humbly He lay, Creator come as creature,
Born on the floor of a hay-scattered stall.
True Son of God, yet bearing human feature,
He entered earth to reverse Adam’s fall.
In towering grace, He laid aside His glory,
And in our place, was sacrificed for sin.
Fall on your knees! O hear the gospel sto-ry!
O night divine, O night when Christ was born!
O night divine, o night when christ was born!

Come then to Him Who lies within the manger,
With joyful shepherds, proclaim Him as Lord.
Let not the Promised Son remain a stranger;
In reverent worship, make Christ your Adored.
Eternal life is theirs who would receive Him;
With grace and peace, their lives He will adorn.
Fall on your knees! Receive the Gift of heaven!
O night divine, O night when Christ was born!
O night divine, o night when christ was born! ',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            66 => [
                'id' => 67,
                'name' => 'O Come All Ye Faithful',
                'ccli_number' => '31054',
                'copyright' => null,
                'lyrics' => 'Oh, come, all ye faithful,
Joyful and triumphant!
Oh, come ye, oh come ye to Bethlehem.
Come and behold him,
Born the King of angels

Oh, come, let us adore him;
Oh, come, let us adore him;
Oh, come, let us adore him,
Christ, the Lord.

Sing, choirs of angels,
Sing in exultation;
Sing, all ye citizens of heav\'n above!
Glory to God,
Glory in the highest;

Yea, Lord, we greet thee,
Born this happy morning;
Jesus, to thee be all glory giv\'n.
Son of the Father,
Now in flesh appearing ',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            67 => [
                'id' => 68,
                'name' => 'Unending Love',
                'ccli_number' => '282315',
                'copyright' => null,
                'lyrics' => 'Father I come to you, lifting up my hands
In the name of Jesus, by your grace I stand
Just because you love me, and I love your son
I know your favor, unending love

I receive your favour, your unending love
Not because I’ve earned it, not for what I’ve done
Just because you love me and I love your son
I know your favour, unending love

Unending love, your unending love
Unending love, your unending love

It’s the presence of Your kingdom
As Your glory ﬁlls this place
And I see how much You love me as I look into Your face
Nothing could be better, there’s nothing I would trade
For Your favour, unending love.',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            68 => [
                'id' => 69,
                'name' => 'Praise Adonai',
                'ccli_number' => '2612742',
                'copyright' => null,
                'lyrics' => 'Who is like Him
The Lion and the Lamb
Seated on the throne
Mountains bow down
Every ocean roars
To the Lord of hosts

Praise Adonai
From the rising of the sun
Till the end of every day
Praise Adonai
All the nations of the earth
All the angels and the saints
Sing Praise

Who is like him
The king of all the earth;
who reigns forevermore
Every knee will bow
And every tongue confess
that Jesus Christ is Lord',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            69 => [
                'id' => 70,
                'name' => 'As The Deer',
                'ccli_number' => '1431',
                'copyright' => null,
                'lyrics' => 'As the deer panteth for the water
So my soul longeth after thee
You alone are my hearts desire
And I long to worship thee

You alone are my strength, my shield
To You alone may my spirit yield
You alone are my hearts desire
And I long to worship thee

You\'re my friend and You are my brother,
Even though you are a king.
I love you more than any other,
So much more than anything.',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            70 => [
                'id' => 71,
                'name' => 'Amazing Grace (My Chains Are Gone)',
                'ccli_number' => '4768151',
                'copyright' => null,
                'lyrics' => '<p>Amazing grace How sweet the sound<br>That saved a wretch like me<br>I once was lost, but now I\'m found<br>Was blind, but now I see</p><p>\'Twas grace that taught my heart to fear<br>And grace my fears relieved<br>How precious did that grace appear<br>The hour I first believed</p><p>My chains are gone I\'ve been set free<br>My God, my Savior has ransomed me<br>And like a flood His mercy reigns<br>Unending love, amazing grace</p><p>Through many dangers, toils and snares<br>I have already come:<br>\'tis grace has brought me safe thus far,<br>and grace will lead me home.</p><p>The Lord has promised good to me<br>His word my hope secures<br>He will my shield and portion be<br>As long as life endures</p><p>Yes, when this flesh and heart shall fail,<br>and mortal life shall cease:<br>I shall possess, within the veil,<br>a life of joy and peace.</p><p>The earth shall soon dissolve like snow<br>The sun forbear to shine<br>But God, Who called me here below,<br>Will be forever mine.<br>Will be forever mine.<br>You are forever mine.</p>',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-07-27 00:49:35',
            ],
            71 => [
                'id' => 72,
                'name' => 'Good Shepherd',
                'ccli_number' => '',
                'copyright' => null,
                'lyrics' => 'Lord Jesus you are my good shepherd,
I shall not want
You make me lie down in green pastures,
Beside waters of rest
You lead me on paths that are upright,
And you restore my soul
These things you do,
You do for love and for the glory of your name

Though I walk through the valley of death’s shadow
I won’t fear because you are with me
Your rod and staff, they comfort me

You have prepared a table for me
In the presence of my foes
You have anointed me with oil,
My cup has overﬂowed
All of my days goodness and mercy
Shall surely follow me
And I will worship you forever;
With you I\'ll always be

In Christ I’ve been forgiven
In Christ I’ve been set free
In Christ I have redemption
Lord you have been good to me
In Christ I’ve been made holy
In Christ I’m sanctiﬁed
In Christ I’m born again
In Christ alone I’m made alive!',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            72 => [
                'id' => 73,
                'name' => 'Nothing But The Blood',
                'ccli_number' => '4329411',
                'copyright' => null,
                'lyrics' => '<p>What can wash away my sin?<br>Nothing but the blood of Jesus;<br>What can make me whole again?<br>Nothing but the blood of Jesus.</p><p>Oh! precious is the flow<br>That makes me white as snow;<br>No other fount I know,<br>Nothing but the blood of Jesus.</p><p>For my pardon, this I see,<br>Nothing but the blood of Jesus;<br>For my cleansing this my plea,<br>Nothing but the blood of Jesus.</p><p>Nothing can for sin atone,<br>Nothing but the blood of Jesus;<br>Naught of good that I have done,<br>Nothing but the blood of Jesus.</p><p>This is all my hope and peace,<br>Nothing but the blood of Jesus;<br>This is all my righteousness,<br>Nothing but the blood of Jesus.</p><p>Now by this I\'ll overcome<br>Nothing but the blood of Jesus,<br>Now by this I\'ll reach my home<br>Nothing but the blood of Jesus.</p><p>Glory! Glory! This I sing<br>Nothing but the blood of Jesus,<br>All my praise for this I bring<br>Nothing but the blood of Jesus.</p>',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-07-27 00:53:40',
            ],
            73 => [
                'id' => 74,
                'name' => 'Psalm 46 (Lord Of Hosts)',
                'ccli_number' => '7053138',
                'copyright' => null,
                'lyrics' => 'O come behold the works of God
The nations at His feet
He breaks the bow and bends the spear
And tells the wars to cease
O Mighty One of Israel
You are on our side
We walk by faith in God who burns the chariots with fire

Lord of Hosts, You\'re with us
With us in the fire
With us as a shelter
With us in the storm
You will lead us
Through the fiercest battle
Oh where else would we go
But with the Lord of Hosts

O God of Jacob, fierce and great
You lift Your voice to speak
The earth it bows and all
The mountains move into the sea
O Lord You know the hearts of men
And still you let them live
O God, who makes the mountains melt
Come wrestle us and win
O God who makes the mountains melt
Come wrestle us and win

Though oceans roar, You are the Lord of all
The one who calms the wind and waves
And makes my heart be still
Though the earth gives way,
The mountains move into the sea
The nations rage, I know my God is in control',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            74 => [
                'id' => 75,
                'name' => 'Blessed Be Your Name',
                'ccli_number' => '',
                'copyright' => null,
                'lyrics' => 'VERSE 1
Incline your ear to my cry O Lord
Let my prayer come before you
My soul is troubled and I’m overwhelmed
Sorrow blinds so I can’t see
Though darkness gathers round
I will call upon your name

CHORUS
Blessed be your name, O Lord,
When you give and take away
Blessed be your name, O Lord,
When my heart is filled with pain
Blessed be your name, O Lord,
My lips will sing your praise
Blessed be your name, O Lord,
I will trust in all your ways

VERSE 2
Hear, O Lord, for to you I call
Hear my cry for your mercy
You are my refuge you are my strength
My salvation and my song
You deliver all who trust in you
Who call upon your name

BRIDGE
Through the trials and the tests
In the midst of my distress
I will trust in you alone!
I will trust in you alone!',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            75 => [
                'id' => 76,
                'name' => 'How Great Thou Art',
                'ccli_number' => '14181',
                'copyright' => null,
                'lyrics' => 'Oh Lord my God, when I in awesome wonder
Consider all the world Thy hands have made
I see the stars, I hear the rolling thunder
Thy power throughout the universe displayed

Then sings my soul, my Savior God to Thee
How great Thou art, how great Thou art
Then sings my soul, my Savior God to Thee
How great Thou art, how great Thou art

And when I think that God, His son not sparing
Sent Him to die I scarce can take it in
That on the cross my burden gladly bearing
He bled and died to take away my sin

When Christ shall come with shout of acclamation
And take me home what joy shall feel my heart
Then I shall bow in humble adoration and there proclaim
My God, how great Thou art',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            76 => [
                'id' => 77,
                'name' => 'Great Is Thy Faithfulness',
                'ccli_number' => '18723',
                'copyright' => null,
                'lyrics' => '<p>Great is Thy faithfulness, O God my Father;<br>There is no shadow of turning with Thee;<br>Thou changest not, Thy compassions, they fail not;<br>As Thou hast been Thou forever wilt be.</p><p>Great is Thy faithfulness!<br>Great is Thy faithfulness!<br>Morning by morning new mercies I see:<br>All I have needed Thy hand hath provided—<br>Great is Thy faithfulness, Lord, unto me!</p><p>Summer and winter and springtime and harvest,<br>Sun, moon, and stars in their courses above<br>Join with all nature in manifold witness<br>To Thy great faithfulness, mercy, and love.</p><p>Pardon for sin and a peace that endureth,<br>Thine own dear presence to cheer and to guide,<br>Strength for today and bright hope for tomorrow—<br>Blessings all mine, with ten thousand beside!</p>',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-07-05 20:19:28',
            ],
            77 => [
                'id' => 78,
                'name' => 'I Surrender All',
                'ccli_number' => '23189',
                'copyright' => null,
                'lyrics' => 'All to Jesus I surrender,
All to him I freely give;
I will ever love and trust him,
In his presence daily live.

I surrender all,
I surrender all,
All to thee, my blessed Savior,
I surrender all.

All to Jesus I surrender,
Humbly at his feet I bow,
Worldly pleasures all forsaken,
Take me, Jesus, take me now.

All to Jesus I surrender;
Make me, Savior, wholly thine;
Let me feel the Holy Spirit,
Truly know that thou art mine.

All to Jesus I surrender,
Lord, I give myself to thee,
Fill me with thy love and power,
Let thy blessing fall on me.

All to Jesus I surrender;
Now I feel the sacred flame.
Oh, the joy of full salvation!
Glory, glory, to his name!',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            78 => [
                'id' => 79,
                'name' => 'Agnus Dei',
                'ccli_number' => '626713',
                'copyright' => null,
                'lyrics' => 'Alleluia
Alleluia
For the Lord God Almighty reigns

Alleluia
Alleluia
For the Lord God Almighty reigns

Alleluia
Holy
Holy are You Lord God Almighty
Worthy is the Lamb
Worthy is the Lamb

You are holy
Holy are you Lord God Almighty
Worthy is the Lamb
Worthy is the Lamb
',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            79 => [
                'id' => 80,
                'name' => 'Jesus Overcame',
                'ccli_number' => '',
                'copyright' => null,
                'lyrics' => 'Because of Jesus my sins are washed away
Because of Jesus my sins are washed away
You know he suffered, bled, and died
And my debt is fully paid

He has conquered! O death where is your sting?
Yes he has conquered! O death where is your sting?
My body may be sown in weakness
But I\'ll be raised in victory

I don\'t have to live in fear and be dismayed
And I don\'t have to live in guilt or be ashamed
No I don\'t have to live in darkness and in pain
I can live a life of love and overcome
Cause Jesus overcame

He is the answer for all that\'s ailing you
He is the answer for all that\'s ailing you
He is the greatest physician
He got some medicine for you

You don\'t have to live in fear and be dismayed
And you don\'t have to live in guilt or be ashamed
No you don\'t have to live in darkness and in pain
You can live a life of love and overcome
Cause Jesus overcame

We have a future and it\'s looking pretty bright
We have a future and it\'s looking pretty bright
And there is one thing for certain
He gonna set everything right

We don\'t have to live in fear and be dismayed
And we don\'t have to live in guilt or be ashamed
No we don\'t have to live in darkness and in pain
We can live a life of love and overcome
Cause Jesus overcame',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            80 => [
                'id' => 81,
                'name' => 'Holy Holy Holy Hosanna',
                'ccli_number' => '1752026',
                'copyright' => null,
                'lyrics' => 'Holy, holy, holy, Lord
God of power and might
Heaven and earth are filled
With Your glory

Holy, holy, holy, Lord
God of power and might
Heaven and earth are filled
With Your glory

Hosanna! Hosanna!
In the highest
Hosanna! Hosanna!
In the highest

Hosanna!!!! Hosanna!!!!
In the highest!!

Oh, With Your glory
Glorious is your name
',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            81 => [
                'id' => 82,
                'name' => 'Jesus Messiah',
                'ccli_number' => '5183443',
                'copyright' => null,
                'lyrics' => 'He became sin, who knew no sin
That we might become His righteousness
He humbled himself and carried the cross

Love so amazing, love so amazing

Jesus Messiah, name above all names
Blessed redeemer, Emmanuel
The rescue for sinners, the ransom from Heaven
Jesus Messiah, Lord of all

His body the bread, his blood the wine
Broken and poured out all for love
The whole earth trembled, and the veil was torn

Love so amazing, love so amazing, yeah

All our hope is in You, all our hope is in You
All the glory to You, God, the light of the world
',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            82 => [
                'id' => 83,
                'name' => 'King of Kings',
                'ccli_number' => '',
                'copyright' => null,
                'lyrics' => 'All power and authority
Is given to the Son
And he sits at the right hand of majesty
He advances like a warrior
Like a soldier in his zeal
With a shout he prevails over his enemies

Glorious!
Arrayed in splendor, clothed in light
Victorious!
Worthy to receive all honor, and wisdom, and might

The forests bow before him
The mountains melt like wax
From his presence earth and heaven ﬂee away
Kings and principalities
Are subject to his name
His tongue like a sword his eyes a ﬂame

Magniﬁed!
Nations lift their voice to sing
Gloriﬁed!
All creation worships Jesus the King of all kings

Your kingdom everlasting, your rule will never end
Every knee will bow before you
And every tongue confess that you are Lord!

Jesus King of kings',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            83 => [
                'id' => 84,
                'name' => 'Let Our Praise To You Be As Incense',
                'ccli_number' => '72477',
                'copyright' => null,
                'lyrics' => 'Let our praise to You be as incense
Let our praise to You be as pillars of Your throne
Let our praise to You be as incense
As we come before You and worship You alone

As we see You in Your splendour
As we gaze upon Your majesty
As we join the hosts of angels
And proclaim together Your holiness

Holy, holy, holy
Holy is the Lord
Holy, holy, holy
Holy is the Lord',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            84 => [
                'id' => 85,
                'name' => 'I Love You Lord',
                'ccli_number' => '25266',
                'copyright' => null,
                'lyrics' => 'I love you, Lord
And I lift my voice
To worship You
Oh, my soul, rejoice!

Take joy my King
In what You hear
Let it be a sweet, sweet sound
In Your ear',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            85 => [
                'id' => 86,
                'name' => 'Jesus Draw Me Close',
                'ccli_number' => '443680',
                'copyright' => null,
                'lyrics' => 'Jesus draw me close
Closer Lord to You
Let the world around me
Fade away
Jesus draw me close
Closer Lord to You
For I desire to worship and obey',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            86 => [
                'id' => 87,
                'name' => 'Stronger',
                'ccli_number' => '5060810',
                'copyright' => null,
                'lyrics' => 'There is love that came for us
Humbled to a sinner\'s cross
You broke my shame and sinfulness
You rose again victorious

Faithfulness none can deny
Through the storm and through the fire
There is truth that sets me free
Jesus Christ who lives in me

You are stronger, You are stronger
Sin is broken, You have saved me
It is written, Christ is risen
Jesus You are Lord of all

No beginning and no end
You\'re my hope and my defense
You came to seek and save the lost
You paid it all upon the cross

You are stronger, You are stronger
Sin is broken, You have saved me
It is written, Christ is risen
Jesus You are Lord of all
There is none

So let Your name be lifted higher
Be lifted higher, be lifted higher
So let Your name be lifted higher
Be lifted higher, be lifted higher

You are stronger, You are stronger
Sin is broken, You have saved me
It is written, Christ is risen
Jesus You are Lord of all
',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            87 => [
                'id' => 88,
                'name' => 'Draw Me Close',
                'ccli_number' => '1459484',
                'copyright' => null,
                'lyrics' => 'Draw me close to You
Never let me go
I lay it all down again
To hear You say that I’m Your friend

You are my desire
No one else will do
’Cause nothing else could ever take Your place
To feel the warmth of Your embrace
Help me find the way
Bring me back to You

You’re all I want
You’re all I’ve ever needed
You’re all I want
Help me know You are near',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            88 => [
                'id' => 89,
                'name' => 'All Glory Be To Christ',
                'ccli_number' => '7008232',
                'copyright' => null,
                'lyrics' => '<p>Should nothing of our efforts stand<br>No legacy survive<br>Unless the Lord does raise the house<br>In vain its builders strive</p><p>To you who boast tomorrow\'s gain<br>Tell me, What is your life?<br>A mist that vanishes at dawn<br>All glory be to Christ!</p><p>All glory be to Christ our king!<br>All glory be to Christ!<br>His rule and reign we\'ll ever sing<br>All glory be to Christ!</p><p>His will be done, His kingdom come<br>On earth as is above<br>Who is Himself our daily bread<br>Praise Him, the Lord of love</p><p>Let living water satisfy<br>The thirsty without price<br>We\'ll take a cup of kindness yet<br>All glory be to Christ!</p><p>When on the day the great I Am<br>The faithful and the true<br>The Lamb who was for sinners slain<br>Is making all things new</p><p>Behold our God shall live with us<br>And be our steadfast light<br>And we shall e\'er his people be<br>All glory be to Christ!</p>',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-08-02 19:31:20',
            ],
            89 => [
                'id' => 90,
                'name' => 'What Child Is This',
                'ccli_number' => '30983',
                'copyright' => null,
                'lyrics' => 'What child is this, who, laid to rest
On Mary\'s lap is sleeping?
Whom angels greet with anthems sweet
While shepherds watch are keeping?

Why lies He in such mean estate,
Where ox and ass are feeding?
Good Christian, fear: for sinners here
The silent Word is pleading.

This, this is Christ the King
Whom shepherds guard and angels sing
Haste, haste to bring him laud
The babe, the son of Mary

So bring him incense, gold, and myrrh
Come, peasant, king, to own him
The King of kings salvation brings
Let loving hearts enthrone him',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            90 => [
                'id' => 91,
                'name' => 'Son of David, Have Mercy on Me',
                'ccli_number' => '',
                'copyright' => null,
                'lyrics' => 'Open my eyes to see
Your grace poured out on me
I stumble as one blind
I will look to you and find
Mercy, in my time of need
Son of David have mercy on me

Place your hands upon my ears
Open them that I might hear
Healing words that bring forth life
I will listen and I’ll find
Mercy, in my time of need
Son of David have mercy on me

Do I wait for a proper time?
Or do I cry out in my need?
Do I risk stepping out of line?
Or do I hold my peace?
Whatever man may say
I don’t want to stay this way
With all that is within me
I will call upon your name... Jesus!

Strengthen my hands and feet
Make straight what’s bent in me
Set me free from all that binds
I will run to you and find
Mercy, in my time of need
Son of David have mercy on me',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            91 => [
                'id' => 92,
                'name' => 'Our Father',
                'ccli_number' => '',
                'copyright' => null,
                'lyrics' => 'Our Father in heaven
Hallowed be thy name
May you be worshipped and honored
Throughout the world you made
Establish your kingdom
Let your will be done
As it is in heaven above us
Make it so in all the earth

Give us tomorrow’s bread
To sustain us here today
Let us taste the power of the age to come
In this present day

Release us from our debts
As we forgive
May we be mindful to offer
The mercy we’ve received

Deliver us from sin
And from the evil one
Help us live in the power of the age to come
Established in the way

Yours is the kingdom
Yours is the power
Yours is the glory
Forevermore
Forevermore',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            92 => [
                'id' => 93,
                'name' => 'Please Help Me Now',
                'ccli_number' => '',
                'copyright' => null,
                'lyrics' => 'How long O Lord will you hide?
How long O Lord will you forget?
This burden I carry within is breaking me down
The light of my eyes grows dim; o God help me now
Please help me now..

How long must I counsel my soul
I’m filled with mourning all the day long
This sorrow deep down in my bones is breaking me down
The breath in my lungs grow faint, o God help me now
Please help me now..

O my God
You have called; so I have come
Just as I am
Weak and afraid; needing your grace
Please hear my prayer
You are humble and true
God I need you

O my soul, trust in the Lord
For his steadfast love, is forevermore
Sing my soul, for he is good
For his steadfast love, is forevermore',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            93 => [
                'id' => 94,
                'name' => 'Father of Lights',
                'ccli_number' => '',
                'copyright' => null,
                'lyrics' => 'It’s for freedom Christ has set us free
We are saved by grace; redeemed from slavery
We’ve been pardoned; our guilt is washed away
We’re forgiven; our debt is fully paid

Every good and perfect gift
Flowing down from above
From the hand of our merciful Father
Father of lights
Ooh Father of lights

When bound in chains, held in captivity
Jesus paid the price for our liberty
Death has no claim; in Christ we’re made alive
We are born again, heirs of eternal life

Every good and perfect gift
Flowing down from above
From the hand of our merciful Father
Father of lights
Ooh Father of lights

Out of the riches of his grace
God is supplying all our need
Those the son sets free
Are free indeed!

Praise to the father
Father of lights
Who has given us all things
All things in Christ',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            94 => [
                'id' => 95,
                'name' => 'Amazing Grace',
                'ccli_number' => '22025',
                'copyright' => null,
                'lyrics' => 'Amazing grace! how sweet the sound,
That saved a wretch; like me!
I once was lost, but now am found,
Was blind, but now I see.

’Twas grace that taught my heart to fear,
And grace my fears relieved;
How precious did that grace appear
The hour I first believed!

Through many dangers, toils and snares
We have already come
T\'was Grace that brought us safe thus far
And Grace will lead us home

The Lord hath promised good to me,
His word my hope secures;
He will my shield and portion be
As long as life endures.

Yes, when this flesh and heart shall fail,
and mortal life shall cease:
I shall possess, within the veil,
a life of joy and peace.

When we’ve been there ten thousand years,
Bright shining as the sun,
We’ve no less days to sing God’s praise
Than when we first begun.',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            95 => [
                'id' => 96,
                'name' => 'We Are One',
                'ccli_number' => '',
                'copyright' => null,
                'lyrics' => 'We are yours; we are one
We have all been made partakers
of the life that\'s in your son
And we sing and rejoice
For new mercies every morning
and your everlasting love
Ooh Ooh
Ooh Ooh

We\'re your church; we are one
We are members of your body
from every tribe and tongue
And we join with one voice
In the anthem of the ages
rising to your throne above
Ooh Ooh
Ooh Ooh

As we gather in your name
you are with us here again ooh

One Lord, one faith; one God and Father over all
Through all, in all, in all
Through all, in all, in all

We are yours; it is done
We\'re adopted to your family
through the victory you\'ve won
There is hope; there is joy
As we lift our hearts to worship
you with gratitude and love
Ooh Ooh
Ooh Ooh',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            96 => [
                'id' => 97,
                'name' => 'One True God',
                'ccli_number' => '',
                'copyright' => null,
                'lyrics' => 'We call you Father, God over all.
The one from whom all life has come
The Creator of heavens reach, the one true God
So we worship, we worship, The Father Holy God
So we worship, we worship, The Father Holy God
You’re the one true God

We call you Jesus the one true son.
Our redeemer, Immanuel
The hope and peace for all to receive, the one true God
So we worship, we worship, The Son of God
So we worship, we worship, The Son of God
You’re the one true God

We call you Spirit, the spirit of God.
The power from heavens throne
The strength of the saints below, the one true God
So we worship, we worship, The Spirit of God
So we worship, we worship, The Spirit of God
You’re the one true God

What a holy mystery, my heart is stirred to praise thee
Father, Son, and Spirit, all my praise, To the one true God

So we worship, we worship, The Father, Spirit, Son
You’re the one true God',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            97 => [
                'id' => 98,
                'name' => 'Fairest Lord Jesus',
                'ccli_number' => '27800',
                'copyright' => null,
                'lyrics' => 'Fairest Lord Jesus, ruler of all nature,
O thou of God and man the Son,
Thee will I cherish, thee will I honor,
thou, my soul\'s glory, joy, and crown.

Fairest Lord Jesus, ruler of all
Fairest Lord Jesus, ruler of all

Fair are the meadows,
fairer still the woodlands,
robed in the blooming garb of spring:
Jesus is fairer, Jesus is purer
who makes the woeful heart to sing.

Fair is the sunshine,
fairer still the moonlight,
and all the twinkling starry host:
Jesus shines brighter, Jesus shines purer
than all the angels heaven can boast.

Beautiful Savior!
Lord of all the nations!
Son of God and Son of Man!
Glory and honor, praise, adoration,
now and forevermore be thine.',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            98 => [
                'id' => 99,
                'name' => 'The Victory',
                'ccli_number' => '5990179',
                'copyright' => null,
                'lyrics' => 'On a hill, Your blood was spilled
Your brow, Your hands, Your feet
With nails and thorns the veil was torn
To make a way for me, You made a way for me

Jesus, Saviour, my God, my King, my Lord
Jesus, Saviour, the victory is Yours

Wrapped and bound, they laid You down
A perfect sacrifice
But in three days, the stone was rolled away
Forever You\'re alive, forever You\'re alive

Jesus, Saviour, my God, my King, my Lord
Jesus, Saviour, the victory is Yours, the victory is Yours

Death has been beaten
The grave has been conquered
Jesus is risen, life ever after
Death has been beaten
The grave has been conquered
Jesus is risen, life ever after',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            99 => [
                'id' => 100,
                'name' => 'His Mercy Is More',
                'ccli_number' => '7065053',
                'copyright' => null,
                'lyrics' => '<p>What love could remember no wrongs we have done<br>Omniscient all knowing He counts not their sum<br>Thrown into a sea without bottom or shore<br>Our sins they are many His mercy is more</p><p>What patience would wait as we constantly roam<br>What Father so tender is calling us home<br>He welcomes the weakest the vilest the poor<br>Our sins they are many His mercy is more</p><p>What riches of kindness He lavished on us<br>His blood was the payment His life was the cost<br>We stood \'neath a debt we could never afford<br>Our sins they are many His mercy is more</p><p>Praise the Lord<br>His mercy is more<br>Stronger than darkness new every morn<br>Our sins they are many His mercy is more</p>',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-07-12 14:12:15',
            ],
            100 => [
                'id' => 101,
                'name' => 'Come Thou Long Expected',
                'ccli_number' => '3606551',
                'copyright' => null,
                'lyrics' => 'Come, Thou long expected Jesus
Born to set Thy people free
From our fears and sins release us
Let us find our rest in Thee
Israel\'s strength and consolation
Hope of all the earth Thou art
Dear desire of every nation
Joy of every longing heart

Born Thy people to deliver
Born a child and yet a king
Born to reign in us forever
Now Thy gracious kingdom bring
By Thine own eternal spirit
Rule in all our hearts alone
By Thine all sufficient merit
Raise us to Thy glorious throne

Come, Thou long expected king
Fill our heart with gladness
Come, Thou long expected king
Fill our heart with gladness

Israel\'s strength and consolation
Hope of all the earth Thou art
Dear desire of every nation
Joy of every longing heart',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            101 => [
                'id' => 102,
                'name' => 'Joy To The World (Joyful Joyful)',
                'ccli_number' => '7128618',
                'copyright' => null,
                'lyrics' => 'Joy to the world the Lord is come
Let earth receive her king
Let every heart prepare Him room
And heaven and nature sing
And heaven and nature sing
And heaven and Heaven and nature sing

We will sing, sing, sing
Joy to the world
We will sing, sing, sing
Joy to the world the savior reigns

Let men their songs employ
While fields and floods rocks hills and plains
Repeat the sounding joy
Repeat the sounding joy
Repeat, repeat the sounding joy

We will sing, sing, sing
Joy to the world
We will sing, sing, sing
He rules the world with truth and grace

And makes the nations prove
The glories of His righteousness
And wonders of His love
And wonders of His love
And wonders, wonders of His love

Joy to the world
We will sing, sing, sing

Joyful, joyful we adore Thee
God of glory, Lord of love
And hearts unfold like flowers before Thee
Opening to the sun above',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            102 => [
                'id' => 103,
                'name' => 'Yet Not I But Through Christ In Me',
                'ccli_number' => '7121852',
                'copyright' => null,
                'lyrics' => '<p>What gift of grace is Jesus my redeemer<br>There is no more for heaven now to give<br>He is my joy, my righteousness, and freedom<br>My steadfast love, my deep and boundless peace</p><p>To this I hold, my hope is only Jesus<br>For my life is wholly bound to His<br>Oh how strange and divine, I can sing, "All is mine"<br>Yet not I, but through Christ in me</p><p>The night is dark but I am not forsaken<br>For by my side, the Saviour He will stay<br>I labour on in weakness and rejoicing<br>For in my need, His power is displayed</p><p>To this I hold, my Shepherd will defend me<br>Through the deepest valley He will lead<br>Oh the night has been won, and I shall overcome<br>Yet not I, but through Christ in me</p><p>No fate I dread, I know I am forgiven<br>The future sure, the price it has been paid<br>For Jesus bled and suffered for my pardon<br>And He was raised to overthrow the grave</p><p>To this I hold, my sin has been defeated<br>Jesus now and ever is my plea<br>Oh the chains are released, I can sing, "I am free"<br>Yet not I, but through Christ in me</p><p>With every breath I long to follow Jesus<br>For He has said that He will bring me home<br>And day by day I know He will renew me<br>Until I stand with joy before the throne</p><p>To this I hold, my hope is only Jesus<br>All the glory evermore to Him<br>When the race is complete, still my lips shall repeat<br>Yet not I, but through Christ in me</p>',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-07-19 22:39:34',
            ],
            103 => [
                'id' => 104,
                'name' => 'Ancient Of Days',
                'ccli_number' => '7121851',
                'copyright' => null,
                'lyrics' => 'Though the nations rage, kingdoms rise and fall
There is still one King reigning over all
So I will not fear for this truth remains
That my God is, the Ancient of Days

None above Him, none before Him
All of time in His hands
For His throne it shall remain and ever stand
All the power, all the glory
I will trust in His name
For my God is, the Ancient of Days

Though the dread of night overwhelms my soul
He is here with me, I am not alone
O His love is sure, and He knows my name
For my God is, the Ancient of Days

Though I may not see what the future brings
I will watch and wait for the Saviour King
Then my joy complete, standing face to face
In the presence of the Ancient of Days

None above Him, none before Him
All of time in His hands
For His throne it shall remain and ever stand
All the power, all the glory
I will trust in His name
For my God is, the Ancient of Days
For my God is, the Ancient of Days',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            104 => [
                'id' => 105,
                'name' => 'There Is One Gospel',
                'ccli_number' => '7199817',
                'copyright' => null,
                'lyrics' => '<p>There is one Gospel on which I stand<br>For all eternity<br>It is my story, my Father\'s plan<br>The Son has rescued me<br>Oh what a Gospel, oh what a peace<br>My highest joy and my deepest need<br>Now and forever He is my light<br>I stand in the Gospel of Jesus Christ</p><p>There is one Gospel to which I cling<br>All else I count as loss<br>For there, where justice and mercy meet<br>He saved me on the cross<br>No more I boast in what I can bring<br>No more I carry the weight of sin<br>For He has brought me from death to life<br>I stand in the Gospel of Jesus Christ</p><p>There is one Gospel where hope is found<br>The empty tomb still speaks<br>For death could not keep my Saviour down<br>He lives and I am free<br>Now on my Saviour, I fix my eyes<br>My life is His and His hope is mine!<br>For He has promised I, too, will rise<br>I stand in the Gospel of Jesus Christ</p><p>And in this Gospel the church is one<br>We do not walk alone<br>We have His Spirit as we press on<br>To lead us safely home<br>And when in glory still I will sing<br>Of this old story that rescued me<br>Praise to my Saviour, the King of life<br>I stand in the Gospel of Jesus Christ</p><p>And when in glory still I will sing<br>Of this old story that rescued me<br>Praise to my Saviour, the King of life<br>I stand in the Gospel of Jesus Christ</p><p>Praise to my Saviour, the King of life<br>I stand in the Gospel of Jesus Christ<br>I stand in the Gospel of Jesus Christ</p>',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-07-12 14:20:57',
            ],
            105 => [
                'id' => 106,
                'name' => 'For The Beauty Of The Earth',
                'ccli_number' => '43200',
                'copyright' => null,
                'lyrics' => '<p>For the beauty of the earth<br>For the glory of the skies<br>For the love which from our birth<br>Over and around us lies</p><p>Lord of all to thee we raise<br>This, our hymn of grateful praise.<br>Lord of all to thee we raise<br>This, our hymn of grateful praise. </p><p>The earth is filled with your glory<br>The earth is filled with your glory</p><p>For the wonder of each hour<br>Of the day and of the night<br>Hill and vale and tree and flower<br>Sun and moon and stars of light</p><p>For each perfect gift of Thine<br>To our world so freely given<br>Graces, human and divine<br>Flowers of earth and buds of heaven</p>',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-08-02 19:25:45',
            ],
            106 => [
                'id' => 107,
                'name' => 'Let Us Love And Sing And Wonder',
                'ccli_number' => '458211',
                'copyright' => null,
                'lyrics' => '<p>Let us love and sing and wonder,<br>let us praise the Savior\'s name!<br>He has hushed the law\'s loud thunder,<br>He has quenched Mount Sinai\'s flame:<br>He has washed us with his blood, (x3)<br>He has brought us nigh to God.</p><p>Let us love the Lord who bought us,<br>pitied us when enemies,<br>called us by his grace and taught us,<br>gave us ears and gave us eyes:<br>he has washed us with his blood, (x3)<br>he presents our souls to God.</p><p>Let us sing, though fierce temptation<br>threaten hard to bear us down!<br>For the Lord, our strong salvation,<br>holds in view the conqu\'ror\'s crown:<br>he who washed us with his blood (x3)<br>soon will bring us home to God.</p><p>Let us wonder; grace and justice<br>join and point to mercy\'s store;<br>when thro\' grace in Christ our trust is,<br>justice smiles and asks no more:<br>he who washed us with his blood (x3)<br>has secured our way to God.</p><p>Let us praise, and join the chorus<br>of the saints enthroned on high;<br>here they trusted him before us,<br>now their praises fill the sky:<br>"You have washed us with your blood; (x3)<br>you are worthy, Lamb of God!"</p><p>Christ has washed us with his blood,<br>He is worthy, Lamb of God!</p>',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-07-27 00:52:15',
            ],
            107 => [
                'id' => 108,
                'name' => 'O Come All You Unfaithful',
                'ccli_number' => '7160115',
                'copyright' => null,
                'lyrics' => 'O come, all you unfaithful;
come, weak and unstable
Come, know you are not alone

O come, barren and waiting ones,
weary of praying
Come, see what your God has done

Christ is born,
Christ is born,
Christ is born for you

O come, bitter and broken,
come with fears unspoken
Come, taste of His perfect love

O come, guilty and hiding ones,
there is no need to run
See what your God has done

He’s the Lamb who was given,
slain for our pardon
His promise is peace for those who believe

So come, though you have nothing,
come, He is the offering
Come, see what your God has done',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            108 => [
                'id' => 109,
                'name' => 'Joyful Joyful',
                'ccli_number' => '4882446',
                'copyright' => null,
                'lyrics' => 'Joyful, joyful, we adore Thee, God of glory, Lord of love
Hearts unfold like flowers before Thee, Opening to the sun above
Melt the clouds of sin and sadness, Drive the dark of doubt away
Giver of immortal gladness, Fill us with the light of day

All Thy works with joy surround Thee, Earth and heaven reflect Thy rays
Stars and angels sing around Thee, Center of unbroken praise
Field and forest, vale and mountain, Flowery meadow, flashing sea
Singing bird and flowing fountain, Call us to rejoice in Thee

Thou art giving and forgiving, Ever blessing, ever blessed
Wellspring of the joy of living, Ocean depth of happy rest
Thou our Father, Christ our Brother, All who live in love are Thine
Teach us how to love each other, Lift us to the joy divine

Mortals, join the happy chorus, Which the morning stars began
Father love is reigning o\'er us, Brother love binds man to man
Ever singing, march we onward, Victors in the midst of strife
Joyful music leads us sunward, In the triumph song of life

Melt the clouds of sin and sadness, Drive the dark of doubt away
Giver of immortal gladness, Fill us with the light of day
',
                'created_at' => '2025-06-02 01:29:21',
                'updated_at' => '2025-06-02 01:29:21',
            ],
            109 => [
                'id' => 110,
                'name' => 'Christ My Righteousness',
                'ccli_number' => null,
                'copyright' => '2024 Reforming Truth Church',
                'lyrics' => '<p>“In Christ” is not just a phrase<br>It’s who I am.<br>The imputed righteousness of Christ<br>is running through my veins.</p><p>Once lost in sin now redeemed<br>By the blood of the lamb<br>He gave up his life for mine<br>And made me his own</p><p>What more to speak, what more to sing<br>But the glory of Christ</p><p>So we sing<br>Glory, glory, hallelujah<br>Christ my Righteousness</p><p>You’ve brought me under your wings<br>And called me your child<br>No longer finding my own way<br>Just resting in your Son.</p><p>I was dead and lost, clothed in sin<br>But God reached out, and called me in<br>Naked I come, no boast I bring<br>Only Christ, only Christ</p>',
                'created_at' => '2025-07-05 19:44:42',
                'updated_at' => '2025-07-05 19:44:42',
            ],
            110 => [
                'id' => 111,
                'name' => 'A Mighty Fortress is Our God',
                'ccli_number' => '42964',
                'copyright' => null,
                'lyrics' => '<p>A mighty fortress is our God<br>A bulwark never failing<br>Our helper He amid the flood<br>Of mortal ills prevailing<br>For still our ancient foe<br>Does seek to work us woe<br>His craft and pow\'r are great<br>And armed with cruel hate<br>On earth is not his equal</p><p>Did we in our own strength confide<br>Our striving would be losing<br>Were not the right Man on our side<br>The Man of God\'s own choosing<br>Dost ask who that may be<br>Christ Jesus it is He<br>Lord Sabaoth His name<br>From age to age the same<br>And He must win the battle</p><p>And tho\' this world with devils filled<br>Should threaten to undo us<br>We will not fear for God hath willed<br>His truth to triumph through us<br>The prince of darkness grim<br>We tremble not for him<br>His rage we can endure<br>For lo his doom is sure<br>One little word shall fell him</p><p>That word above all earthly pow\'rs<br>No thanks to them abideth<br>The Spirit and the gifts are ours<br>Thru Him who with us sideth<br>Let goods and kindred go<br>This mortal life also<br>The body they may kill<br>God\'s truth abideth still<br>His kingdom is forever</p>',
                'created_at' => '2025-07-19 13:08:26',
                'updated_at' => '2025-07-19 13:08:26',
            ],
            111 => [
                'id' => 112,
                'name' => 'Grace Alone',
                'ccli_number' => '7004659',
                'copyright' => null,
                'lyrics' => '<p>I was an orphan lost at the fall<br>Running away when I\'d hear you call<br>But Father you worked your will<br>I had no righteousness of my own<br>I had no right to draw near your throne<br>But Father you loved me still</p><p>And in love before you laid the world\'s foundation<br>You predestined to adopt me as your own<br>You have raised me up so high above my station<br>I\'m a child of God by grace and grace alone</p><p>You left your home to seek out the lost<br>You knew the great and terrible cost<br>But Jesus your face was set<br>I worked my fingers down to the bone<br>But nothing I did could ever atone<br>But Jesus you paid my debt</p><p>By Your blood I have redemption and salvation<br>Lord you died that I might reap what you have sown<br>And you rose that I might be a new creation<br>I am born again by grace and grace alone</p><p>I was in darkness all of my life<br>I never knew the day from the night<br>But Spirit you made me see<br>I swore I knew the way on my own<br>Head full of rocks a heart made of stone<br>But Spirit you moved in me</p><p>And at your touch my sleeping spirit was awakened<br>On my darkened heart the light of Christ has shone<br>Called into a kingdom that cannot be shaken<br>Heaven\'s citizen by grace and grace alone</p><p>So I\'ll stand in faith by grace and grace alone<br>I will run the race by grace and grace alone<br>I will slay my sin by grace and grace alone<br>I will reach the end by grace and grace alone</p>',
                'created_at' => '2025-07-19 19:48:08',
                'updated_at' => '2025-07-19 19:48:08',
            ],
            112 => [
                'id' => 113,
                'name' => 'I Know That My Redeemer Lives',
                'ccli_number' => '7218996',
                'copyright' => '© 2022 Getty Music Hymns and Songs, Getty Music Publishing, Jordan Kauflin Music, Laura\'s Stories and Songs, Love Your Enemies Publishing, and Matthew Merker Music',
                'lyrics' => '<p>I know that my Redeemer lives<br>What hope this sweet assurance gives<br>That He who gave His life for me<br>Arose with healing in His wings<br>He lives the tomb is empty still<br>Redemption\'s promise He fulfilled<br>No condemnation now remains<br>The stone of death is rolled away</p><p>My Redeemer lives; My Redeemer lives<br>On heaven\'s throne and in my very soul<br>I know that my Redeemer lives</p><p>I know that my Redeemer lives<br>And all my life is bound to His<br>In realms above He intercedes<br>Our sinless Savior Perfect Priest<br>No fear can follow where He guides<br>His constant presence is my light<br>No power on earth or heav\'n above<br>Can separate us from His love</p><p>I know that my Redeemer lives<br>In life and death I trust in Him<br>My soul secure my future safe<br>He\'ll not forsake me to the grave<br>He lives and He will not delay<br>My eyes will wake to brightest day<br>And in my flesh I\'ll see Him stand<br>When Christ in glory comes again</p><p>And every eye will see Him stand<br>When Christ in glory comes again</p>',
                'created_at' => '2025-08-02 19:37:09',
                'updated_at' => '2025-08-02 19:37:09',
            ],
        ]);

    }
}
