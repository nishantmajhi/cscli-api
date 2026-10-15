/* Unit 2: Elements of C
 * Demonstrates basic data types, sizeof, and printing each with its
 * matching format specifier. */
#include <stdio.h>

int main(void) {
    char c = 'A';
    int i = 42;
    float f = 3.14f;
    double d = 3.14159265;
    long l = 100000L;
    unsigned int u = 4000000000u;

    printf("char   : %c  (size = %zu bytes)\n", c, sizeof(c));
    printf("int    : %d  (size = %zu bytes)\n", i, sizeof(i));
    printf("float  : %f  (size = %zu bytes)\n", f, sizeof(f));
    printf("double : %lf (size = %zu bytes)\n", d, sizeof(d));
    printf("long   : %ld (size = %zu bytes)\n", l, sizeof(l));
    printf("unsigned int : %u (size = %zu bytes)\n", u, sizeof(u));

    return 0;
}
