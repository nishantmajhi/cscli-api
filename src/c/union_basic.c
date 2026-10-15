/* Unit 8: Structure and Union
 * Union - all members share the same memory location, unlike a
 * structure where each member has its own. */
#include <stdio.h>

union Data {
    int i;
    float f;
    char str[20];
};

int main(void) {
    union Data data;

    printf("Size of union Data = %zu bytes\n", sizeof(data));

    data.i = 10;
    printf("data.i = %d\n", data.i);

    data.f = 3.14f;
    printf("data.f = %.2f (data.i is now meaningless: %d)\n", data.f, data.i);

    return 0;
}
