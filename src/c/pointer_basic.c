/* Unit 9: Pointers
 * Introduction to pointers, and the & (address-of) and * (dereference)
 * operators. */
#include <stdio.h>

int main(void) {
    int num = 42;
    int *ptr = &num; /* ptr stores the address of num */

    printf("Value of num        : %d\n", num);
    printf("Address of num      : %p\n", (void *)&num);
    printf("Value stored in ptr : %p\n", (void *)ptr);
    printf("Value pointed by ptr: %d\n", *ptr); /* dereference */

    *ptr = 100; /* modify num through the pointer */
    printf("num after *ptr = 100: %d\n", num);

    return 0;
}
